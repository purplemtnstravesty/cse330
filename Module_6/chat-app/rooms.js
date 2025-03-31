// --- Data Structures ---
// Store room information: password, creator, banned users, user list (socketId -> username)
let rooms = {
    // 'Room Name': {
    //     name: 'Room Name',
    //     password: null | 'somepassword', // Store plaintext for prototype ONLY
    //     creator: 'socketIdOfTheCreator',
    //     bannedUsers: ['bannedUsername1'],
    //     users: { // Map socket IDs to usernames within this room
    //         'socketId1': 'Alice',
    //         'socketId2': 'Bob'
    //     }
    // }
};

// Store user information globally (socketId -> { username, currentRoom })
let users = {
    // 'socketId1': { username: 'Alice', currentRoom: 'Room Name' }
};

// --- Express Setup ---
// Serve static files from the 'public' directory
app.use(express.static(path.join(__dirname, 'public')));

// --- Helper Functions ---

// Get usernames for a specific room
function getUsernamesInRoom(roomName) {
    return rooms[roomName] ? Object.values(rooms[roomName].users) : [];
}

// Get socket ID by username within a specific room
function findSocketIdByUsername(roomName, username) {
    if (!rooms[roomName] || !rooms[roomName].users) {
        return null;
    }
    for (const [socketId, name] of Object.entries(rooms[roomName].users)) {
        if (name === username) {
            return socketId;
        }
    }
    return null;
}

// Send updated list of rooms (names and privacy status) to everyone
function broadcastRoomList() {
    const roomList = Object.values(rooms).map(room => ({
        name: room.name,
        isPrivate: !!room.password // True if password exists, false otherwise
    }));
    io.emit('update_rooms', roomList);
}

// Handle a user leaving their current room (update state, notify others)
function leaveCurrentRoom(socket) {
    const userId = socket.id;
    const userInfo = users[userId];

    if (!userInfo || !userInfo.currentRoom) {
        return; // User isn't in a room
    }

    const roomName = userInfo.currentRoom;
    const room = rooms[roomName];

    if (!room) {
        // Room might have been deleted or state is inconsistent
        delete userInfo.currentRoom;
        return;
    }

    // Remove user from room's user list
    if (room.users[userId]) {
        const leavingUsername = room.users[userId];
        delete room.users[userId];

        // Update user's global state
        delete userInfo.currentRoom;

        // Notify remaining users in the room
        const remainingUsernames = getUsernamesInRoom(roomName);
        io.to(roomName).emit('user_left', { username: leavingUsername });
        io.to(roomName).emit('update_users', remainingUsernames);

        // Tell the actual socket to leave the Socket.IO room
        socket.leave(roomName);
        console.log(`${leavingUsername} (${userId}) left room ${roomName}`);

        // Optional: Delete room if empty? (Consider if creator leaves)
        // if (remainingUsernames.length === 0) {
        //     console.log(`Room ${roomName} is empty, deleting.`);
        //     delete rooms[roomName];
        //     broadcastRoomList(); // Update everyone's room list
        // }
    }
}


// --- Socket.IO Connection Handling ---
io.on('connection', (socket) => {
    const userId = socket.id;
    console.log(`User connected: ${userId}`);

    // Initialize user entry
    users[userId] = { username: `User_${userId.substring(0, 4)}`, currentRoom: null }; // Default username
    socket.emit('your_info', { id: userId, username: users[userId].username }); // Send initial info

    // Send current room list on connection
    broadcastRoomList();

    // Set Username
    socket.on('set_username', (newUsername) => {
        const trimmedUsername = newUsername.trim();
        if (trimmedUsername && trimmedUsername.length > 0 && trimmedUsername.length <= 20) {
            const oldUsername = users[userId].username;
            users[userId].username = trimmedUsername;
            console.log(`User ${userId} (${oldUsername}) set username to: ${trimmedUsername}`);
            socket.emit('username_set', { success: true, username: trimmedUsername });

            // Update username in their current room, if any
            const currentRoomName = users[userId].currentRoom;
            if (currentRoomName && rooms[currentRoomName]) {
                rooms[currentRoomName].users[userId] = trimmedUsername;
                // Broadcast updated user list to the room
                io.to(currentRoomName).emit('update_users', getUsernamesInRoom(currentRoomName));
            }
        } else {
            socket.emit('username_set', { success: false, message: 'Invalid username (must be 1-20 chars).' });
        }
    });

    // Create Room
    socket.on('create_room', (data) => {
        const roomName = data.roomName?.trim();
        const password = data.password || null; // null if empty/undefined

        if (!roomName || roomName.length === 0 || roomName.length > 30) {
            return socket.emit('create_error', { message: 'Invalid room name (1-30 chars).' });
        }
        if (rooms[roomName]) {
            return socket.emit('create_error', { message: `Room '${roomName}' already exists.` });
        }

        // Leave previous room before creating/joining a new one
        leaveCurrentRoom(socket);

        // Create the room
        rooms[roomName] = {
            name: roomName,
            password: password,
            creator: userId,
            bannedUsers: [],
            users: {} // Initialize empty user list
        };
        console.log(`Room '${roomName}' created by ${users[userId].username} (${userId})`);

        // Automatically join the creator to the room
        rooms[roomName].users[userId] = users[userId].username;
        users[userId].currentRoom = roomName;
        socket.join(roomName);

        socket.emit('room_created', { success: true, roomName: roomName, isCreator: true }); // Inform creator
        broadcastRoomList(); // Update room list for everyone
        // Send initial user list for the new room
        io.to(roomName).emit('update_users', getUsernamesInRoom(roomName));
    });

    // Join Room
    socket.on('join_room', (data) => {
        const roomName = data.roomName;
        const password = data.password || null;
        const username = users[userId].username;

        if (!rooms[roomName]) {
            return socket.emit('join_error', { message: `Room '${roomName}' does not exist.` });
        }
        const room = rooms[roomName];

        if (room.bannedUsers.includes(username)) {
            return socket.emit('join_error', { message: `You are banned from room '${roomName}'.` });
        }
        if (room.password && room.password !== password) {
            return socket.emit('join_error', { message: 'Incorrect password.' });
        }
        if (room.users[userId]) {
             return socket.emit('join_error', { message: `You are already in room '${roomName}'.` });
        }

        // Leave previous room first
        leaveCurrentRoom(socket);

        // Join the new room
        room.users[userId] = username;
        users[userId].currentRoom = roomName;
        socket.join(roomName);

        console.log(`${username} (${userId}) joined room ${roomName}`);

        // Notify the joining user
        socket.emit('join_success', {
             roomName: roomName,
             isCreator: room.creator === userId // Let client know if they are the creator
        });

        // Notify others in the room
        socket.to(roomName).emit('user_joined', { username: username });

        // Send updated user list to everyone in the room
        io.to(roomName).emit('update_users', getUsernamesInRoom(roomName));
    });

    // Handle Chat Messages
    socket.on('chat_message', (data) => {
        const message = data.message?.trim();
        const username = users[userId].username;
        const roomName = users[userId].currentRoom;

        if (!roomName || !rooms[roomName]) {
            return socket.emit('chat_error', { message: "You are not currently in a room." });
        }
        if (!message || message.length === 0 || message.length > 500) {
            return socket.emit('chat_error', { message: "Invalid message (1-500 chars)." });
        }

        // Broadcast message to the room
        io.to(roomName).emit('chat_message', { username: username, message: message });
    });

    // Handle Private Messages
    socket.on('private_message', (data) => {
        const targetUsername = data.toUsername;
        const message = data.message?.trim();
        const senderUsername = users[userId].username;
        const roomName = users[userId].currentRoom;

        if (!roomName || !rooms[roomName]) {
            return socket.emit('pm_error', { message: "You must be in a room to send PMs." });
        }
        if (!message || message.length === 0 || message.length > 500) {
            return socket.emit('pm_error', { message: "Invalid message (1-500 chars)." });
        }
        if (targetUsername === senderUsername) {
             return socket.emit('pm_error', { message: "You cannot send a PM to yourself." });
        }

        const targetSocketId = findSocketIdByUsername(roomName, targetUsername);

        if (targetSocketId) {
            // Send PM to the target user
            io.to(targetSocketId).emit('private_message', {
                fromUsername: senderUsername,
                message: message
            });
            // Confirm PM sent to the sender
            socket.emit('pm_sent', { toUsername: targetUsername, message: message });
            console.log(`PM from ${senderUsername} to ${targetUsername}`);
        } else {
            socket.emit('pm_error', { message: `User '${targetUsername}' not found in this room.` });
        }
    });

    // Kick User
    socket.on('kick_user', (data) => {
        const usernameToKick = data.username;
        const roomName = users[userId].currentRoom;
        const room = rooms[roomName];

        // Authorization & Validation
        if (!room) return socket.emit('kick_error', { message: "Not in a room." });
        if (room.creator !== userId) return socket.emit('kick_error', { message: "Only the room creator can kick users." });
        if (usernameToKick === users[userId].username) return socket.emit('kick_error', { message: "You cannot kick yourself." });

        const targetSocketId = findSocketIdByUsername(roomName, usernameToKick);
        if (!targetSocketId) return socket.emit('kick_error', { message: `User '${usernameToKick}' not found.` });

        // Perform Kick
        const targetSocket = io.sockets.sockets.get(targetSocketId);
        if (targetSocket) {
            targetSocket.emit('kicked', { roomName: roomName, kicker: users[userId].username });
            leaveCurrentRoom(targetSocket); // Reuse leave logic to handle state cleanup and notifications
            io.to(roomName).emit('user_kicked', { kickedUsername: usernameToKick, kickerUsername: users[userId].username });
            console.log(`${users[userId].username} kicked ${usernameToKick} from ${roomName}`);
            socket.emit('kick_success', { kickedUsername: usernameToKick });
        } else {
             // Should not happen if findSocketIdByUsername worked, but handle defensively
             socket.emit('kick_error', { message: `Could not find socket for user '${usernameToKick}'.` });
             // Manually remove user if socket lookup failed but ID was in list
             if (room.users[targetSocketId]) {
                 delete room.users[targetSocketId];
                 io.to(roomName).emit('update_users', getUsernamesInRoom(roomName));
             }
        }
    });

    // Ban User
    socket.on('ban_user', (data) => {
        const usernameToBan = data.username;
        const roomName = users[userId].currentRoom;
        const room = rooms[roomName];
        const kickerUsername = users[userId].username;

        // Authorization & Validation
        if (!room) return socket.emit('ban_error', { message: "Not in a room." });
        if (room.creator !== userId) return socket.emit('ban_error', { message: "Only the room creator can ban users." });
        if (usernameToBan === kickerUsername) return socket.emit('ban_error', { message: "You cannot ban yourself." });

        // Add to ban list (if not already banned)
        if (!room.bannedUsers.includes(usernameToBan)) {
            room.bannedUsers.push(usernameToBan);
             console.log(`${kickerUsername} banned ${usernameToBan} from ${roomName}`);
        } else {
             return socket.emit('ban_error', { message: `${usernameToBan} is already banned.` });
        }

        // Kick the user immediately after banning
        const targetSocketId = findSocketIdByUsername(roomName, usernameToBan);
        if (targetSocketId) {
             const targetSocket = io.sockets.sockets.get(targetSocketId);
             if (targetSocket) {
                 targetSocket.emit('kicked', { roomName: roomName, kicker: kickerUsername, banned: true });
                 leaveCurrentRoom(targetSocket); // Reuse leave logic
                 io.to(roomName).emit('user_banned', { bannedUsername: usernameToBan, bannerUsername: kickerUsername });
                 socket.emit('ban_success', { bannedUsername: usernameToBan });
             } else {
                 // If socket not found, still remove from user list if present
                 if (room.users[targetSocketId]) {
                    delete room.users[targetSocketId];
                    io.to(roomName).emit('update_users', getUsernamesInRoom(roomName));
                    io.to(roomName).emit('user_banned', { bannedUsername: usernameToBan, bannerUsername: kickerUsername });
                    socket.emit('ban_success', { bannedUsername: usernameToBan });
                 } else {
                      socket.emit('ban_error', { message: `User '${usernameToBan}' was banned but could not be kicked (already left?).` });
                 }
             }
        } else {
              // User might have already left, but ban is applied
              io.to(roomName).emit('user_banned', { bannedUsername: usernameToBan, bannerUsername: kickerUsername });
              socket.emit('ban_success', { bannedUsername: usernameToBan });
        }
    });

    // Disconnect
    socket.on('disconnect', () => {
        console.log(`User disconnected: ${userId} (${users[userId]?.username || 'Unknown'})`);
        leaveCurrentRoom(socket);
        delete users[userId]; // Clean up global user entry
    });
});

// --- Start Server ---
http.listen(port, () => {
    console.log(`Server running at http://localhost:${port}/`);
    console.log('Ensure .gitignore contains node_modules/');
});