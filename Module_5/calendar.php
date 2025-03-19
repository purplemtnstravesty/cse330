<?php
session_start();

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);

// Only require database and get username if logged in
require 'database.php';

if ($is_logged_in) {
    // Get the username for display
    $username = $_SESSION['username'];
}

// Get current month and year (default to current date)
$month = isset($_GET['month']) ? intval($_GET['month']) : intval(date('m')) - 1; // Adjust to 0-indexed month
$year = isset($_GET['year']) ? intval($_GET['year']) : intval(date('Y'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Calendar - NewsAgg</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/dark.css">
    <style>
        /* Calendar Container */
        .calendar-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        /* Calendar Grid */
        #calendar-grid {
            display: flex;
            flex-direction: column;
            border: 1px solid #555;
            border-radius: 5px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        /* Calendar Header */
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .month-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .month-title {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        /* Calendar Week Row */
        .week-row {
            display: flex;
            width: 100%;
            min-height: 20px; /* Minimum height for headers */
        }
        
        /* Day Headers */
        .day-header {
            flex: 1;
            text-align: center;
            font-weight: bold;
            padding: 10px;
            background-color: #444;
            border-right: 1px solid #555;
        }
        
        .day-header:last-child {
            border-right: none;
        }

        /* Calendar Days */
        .calendar-day {
            flex: 1;
            height: 120px;
            min-height: 120px;
            max-height: 120px;
            border-right: 1px solid #555;
            border-bottom: 1px solid #555;
            padding: 5px;
            position: relative;
            background-color: #333;
            overflow-y: auto;
        }
        
        .calendar-day:last-child {
            border-right: none;
        }
        
        .week-row:last-child .calendar-day {
            border-bottom: none;
        }

        .calendar-day:hover {
            background-color: #3a3a3a;
        }

        .day-number {
            position: absolute;
            top: 5px;
            right: 10px;
            font-weight: bold;
        }
        
        .other-month {
            background-color: #2a2a2a;
            color: #888;
        }

        /* Today Highlight */
        .calendar-day.today {
            background-color: rgba(66, 133, 244, 0.2);
            border: 1px solid #4285f4;
        }

        /* Events List */
        .events-list {
            margin-top: 25px;
            max-height: 70px;
            overflow-y: auto;
        }

        .event {
            background-color: #4285f4;
            border-radius: 3px;
            margin-bottom: 5px;
            padding: 5px;
            font-size: 0.9em;
            cursor: pointer;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: white;
            max-width: 95%;
        }

        .event:hover {
            background-color: #5c9cff;
        }

        .event-time {
            font-weight: bold;
            margin-right: 5px;
        }

        /* Event Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
        }

        .modal-content {
            background-color: #333;
            margin: 10% auto;
            padding: 20px;
            border-radius: 5px;
            width: 60%;
            max-width: 500px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            animation: modalFadeIn 0.3s;
        }
        
        @keyframes modalFadeIn {
            from {opacity: 0; transform: translateY(-20px);}
            to {opacity: 1; transform: translateY(0);}
        }

        .close-button {
            float: right;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .close-button:hover {
            color: #f44336;
        }

        /* Form Styling */
        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
        }

        .form-group input, 
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #555;
            background-color: #444;
            color: white;
        }

        .form-actions {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        .delete-button {
            background-color: #f44336;
        }
        
        /* Event Details Card Styling */
        #event-details-title {
            margin-top: 0;
            color: #4285f4;
        }
        
        #event-details-datetime {
            font-style: italic;
            color: #aaa;
        }
        
        #event-details-description {
            background-color: #2a2a2a;
            padding: 10px;
            border-radius: 4px;
            margin: 15px 0;
            min-height: 50px;
        }

        /* Add these styles to your existing CSS */
        .shared-users-container {
            margin-top: 10px;
        }

        .shared-user-chip {
            display: inline-block;
            background-color: #4285f4;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .shared-user-chip .remove-user {
            margin-left: 5px;
            cursor: pointer;
        }

        .shared-user-chip .remove-user:hover {
            color: #f44336;
        }

        .event.shared {
            background-color: #9c27b0;
        }

        .event.shared-with-me {
            background-color: #009688;
        }

        #event-details-owner {
            font-weight: bold;
            color: #4285f4;
        }

        /* Update the event details modal to show tags */
        .tags-container {
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .tag {
            background-color: #4285f4;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.9em;
        }

        .tag.remove {
            background-color: #f44336;
            cursor: pointer;
        }

        .tag.remove:hover {
            background-color: #d32f2f;
        }

        /* Tag styling */
        .tags-container {
            margin: 10px 0;
        }

        .tag-chip {
            display: inline-block;
            background-color: #ff9800;
            color: white;
            padding: 3px 8px;
            border-radius: 16px;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 0.85em;
        }

        .tag-chip .remove-tag {
            margin-left: 5px;
            cursor: pointer;
        }

        .tag-chip .remove-tag:hover {
            color: #f44336;
        }

        .tag-filter {
            margin-right: 10px;
            padding: 8px;
            border-radius: 4px;
            background-color: #444;
            color: white;
            border: 1px solid #555;
        }

        .calendar-controls {
            display: flex;
            align-items: center;
        }

        /* Add tag colors to events */
        .event[data-tag="work"] {
            background-color: #f44336;
        }

        .event[data-tag="personal"] {
            background-color: #4caf50;
        }

        .event[data-tag="important"] {
            background-color: #ff9800;
        }

        .event[data-tag="meeting"] {
            background-color: #9c27b0;
        }

        .event[data-tag="appointment"] {
            background-color: #00bcd4;
        }
    </style>
</head>
<body>
    <header>
        <h1>My Calendar</h1>
        <nav>
            <?php if ($is_logged_in): ?>
                <a href="dashboard.php">Dashboard</a> | 
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <a href="login.php">Login</a> | 
                <a href="register.php">Register</a>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?php if (!$is_logged_in): ?>
            <div class="message-box">
                <p>You are viewing the calendar as a guest. <a href="login.php">Login</a> or <a href="register.php">Register</a> to add and manage your events.</p>
            </div>
        <?php else: ?>
            <h2>Welcome, <?php echo htmlspecialchars($username); ?></h2>
        <?php endif; ?>
        
        <div class="calendar-container">
            <div class="calendar-header">
                <div class="month-nav">
                    <button id="prev-month">&lt; Previous</button>
                    <h2 class="month-title" id="current-month"></h2>
                    <button id="next-month">Next &gt;</button>
                </div>
                <div class="calendar-controls">
                    <select id="tag-filter" class="tag-filter">
                        <option value="">All Events</option>
                        <!-- Tags will be populated by JavaScript -->
                    </select>
                    <button id="add-event-btn">+ Add Event</button>
                </div>
            </div>
            
            <div id="calendar-grid">
                <!-- Calendar will be generated here by JavaScript -->
            </div>
        </div>

        <!-- Add/Edit Event Modal -->
        <div id="event-modal" class="modal">
            <div class="modal-content">
                <span class="close-button" id="close-modal">&times;</span>
                <h2 id="modal-title">Add Event</h2>
                <form id="event-form">
                    <input type="hidden" id="event-id">
                    <div class="form-group">
                        <label for="event-name">Event Name:</label>
                        <input type="text" id="event-name" name="event_name" required>
                    </div>
                    <div class="form-group">
                        <label for="event-datetime">Date and Time:</label>
                        <input type="datetime-local" id="event-datetime" name="event_datetime" required>
                    </div>
                    <div class="form-group">
                        <label for="event-description">Description:</label>
                        <textarea id="event-description" name="event_description" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="event-tags">Tags (comma separated):</label>
                        <input type="text" id="event-tags" name="event_tags" placeholder="e.g. work, important, personal">
                        <div id="event-tags-list" class="tags-container"></div>
                    </div>
                    <div class="form-group">
                        <label for="shared-users">Share with (usernames, comma separated):</label>
                        <input type="text" id="shared-users" name="shared_users" placeholder="e.g. john, mary, alex">
                        <div id="shared-users-list" class="shared-users-container"></div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" id="save-event-btn">Save Event</button>
                        <button type="button" id="delete-event-btn" class="delete-button" style="display: none;">Delete Event</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Event Details Modal -->
        <div id="event-details-modal" class="modal">
            <div class="modal-content">
                <span class="close-button" id="close-details-modal">&times;</span>
                <h2 id="event-details-title"></h2>
                <p id="event-details-datetime"></p>
                <p id="event-details-owner"></p>
                <div id="event-details-tags" class="tags-container"></div>
                <div id="event-details-description"></div>
                <div id="event-details-sharing" class="shared-users-container">
                    <h3>Shared with:</h3>
                    <div id="event-details-shared-users"></div>
                </div>
                <div class="form-actions">
                    <button id="edit-event-btn">Edit Event</button>
                    <button id="delete-event-details-btn" class="delete-button">Delete Event</button>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> NewsAgg</p>
    </footer>

    <script>
    /* * * * * * * * * * * * * * * * * * * *\
     *               Module 4              *
     *      Calendar Helper Functions      *
     *                                     *
     *        by Shane Carr '15 (TA)       *
     *  Washington University in St. Louis *
     *    Department of Computer Science   *
     *               CSE 330S              *
     *                                     *
     *      Last Update: October 2017      *
    \* * * * * * * * * * * * * * * * * * * */

    (function () {
        "use strict";

        /* Date.prototype.deltaDays(n)
         * 
         * Returns a Date object n days in the future.
         */
        Date.prototype.deltaDays = function (n) {
            // relies on the Date object to automatically wrap between months for us
            return new Date(this.getFullYear(), this.getMonth(), this.getDate() + n);
        };

        /* Date.prototype.getSunday()
         * 
         * Returns the Sunday nearest in the past to this date (inclusive)
         */
        Date.prototype.getSunday = function () {
            return this.deltaDays(-1 * this.getDay());
        };
    }());

    /** Week
     * 
     * Represents a week.
     * 
     * Functions (Methods):
     *	.nextWeek() returns a Week object sequentially in the future
     *	.prevWeek() returns a Week object sequentially in the past
     *	.contains(date) returns true if this week's sunday is the same
     *		as date's sunday; false otherwise
     *	.getDates() returns an Array containing 7 Date objects, each representing
     *		one of the seven days in this month
     */
    function Week(initial_d) {
        "use strict";

        this.sunday = initial_d.getSunday();
            
        
        this.nextWeek = function () {
            return new Week(this.sunday.deltaDays(7));
        };
        
        this.prevWeek = function () {
            return new Week(this.sunday.deltaDays(-7));
        };
        
        this.contains = function (d) {
            return (this.sunday.valueOf() === d.getSunday().valueOf());
        };
        
        this.getDates = function () {
            var dates = [];
            for(var i=0; i<7; i++){
                dates.push(this.sunday.deltaDays(i));
            }
            return dates;
        };
    }

    /** Month
     * 
     * Represents a month.
     * 
     * Properties:
     *	.year == the year associated with the month
     *	.month == the month number (January = 0)
     * 
     * Functions (Methods):
     *	.nextMonth() returns a Month object sequentially in the future
     *	.prevMonth() returns a Month object sequentially in the past
     *	.getDateObject(d) returns a Date object representing the date
     *		d in the month
     *	.getWeeks() returns an Array containing all weeks spanned by the
     *		month; the weeks are represented as Week objects
     */
    function Month(year, month) {
        "use strict";
        
        this.year = year;
        this.month = month;
        
        this.nextMonth = function () {
            return new Month( year + Math.floor((month+1)/12), (month+1) % 12);
        };
        
        this.prevMonth = function () {
            return new Month( year + Math.floor((month-1)/12), (month+11) % 12);
        };
        
        this.getDateObject = function(d) {
            return new Date(this.year, this.month, d);
        };
        
        this.getWeeks = function () {
            var firstDay = this.getDateObject(1);
            var lastDay = this.nextMonth().getDateObject(0);
            
            var weeks = [];
            var currweek = new Week(firstDay);
            weeks.push(currweek);
            while(!currweek.contains(lastDay)){
                currweek = currweek.nextWeek();
                weeks.push(currweek);
            }
            
            return weeks;
        };
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Current view month and year
        let currentMonth = new Month(<?php echo $year; ?>, <?php echo $month; ?>);
        
        // DOM elements
        const calendarGrid = document.getElementById('calendar-grid');
        const currentMonthElement = document.getElementById('current-month');
        const prevMonthButton = document.getElementById('prev-month');
        const nextMonthButton = document.getElementById('next-month');
        const addEventButton = document.getElementById('add-event-btn');
        
        // Check if user is logged in
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;
        
        // Hide add event button for non-logged in users
        if (!isLoggedIn) {
            addEventButton.style.display = 'none';
        }
        
        // Event modal elements
        const eventModal = document.getElementById('event-modal');
        const closeModalButton = document.getElementById('close-modal');
        const eventForm = document.getElementById('event-form');
        const eventIdInput = document.getElementById('event-id');
        const eventNameInput = document.getElementById('event-name');
        const eventDatetimeInput = document.getElementById('event-datetime');
        const eventDescriptionInput = document.getElementById('event-description');
        const saveEventButton = document.getElementById('save-event-btn');
        const deleteEventButton = document.getElementById('delete-event-btn');
        const modalTitle = document.getElementById('modal-title');
        
        // Event details modal elements
        const eventDetailsModal = document.getElementById('event-details-modal');
        const closeDetailsModalButton = document.getElementById('close-details-modal');
        const eventDetailsTitle = document.getElementById('event-details-title');
        const eventDetailsDatetime = document.getElementById('event-details-datetime');
        const eventDetailsDescription = document.getElementById('event-details-description');
        const editEventButton = document.getElementById('edit-event-btn');
        const deleteEventDetailsButton = document.getElementById('delete-event-details-btn');
        
        // Events data storage
        let eventsData = {};
        
        // Add these variables to your JavaScript
        let allUsers = []; // Will store all users for sharing
        let selectedSharedUsers = []; // Will store selected users for current event
        let selectedTags = []; // Will store selected tags for current event
        let currentTagFilter = ''; // Current tag filter
        
        document.getElementById("event-tags").addEventListener("keydown", function(e) {
            if (e.key === "Enter" || e.key === ",") {
                e.preventDefault();
                const tag = this.value.trim();
                if (tag && !selectedTags.includes(tag)) {
                    selectedTags.push(tag);
                    addTagChip(tag); // Visually add tag to UI
                }
                this.value = ""; // Clear input field
            }
        });

        function addTagChip(tag) {
            const tagList = document.getElementById("event-tags-list");
            const chip = document.createElement("span");
            chip.className = "tag-chip";
            chip.textContent = tag;

            const removeButton = document.createElement("span");
            removeButton.className = "remove-tag";
            removeButton.textContent = " ×";
            removeButton.onclick = function() {
                selectedTags = selectedTags.filter(t => t !== tag);
                chip.remove();
            };

            chip.appendChild(removeButton);
            tagList.appendChild(chip);
        }

        // Initialize shared user input
        document.getElementById("shared-users").addEventListener("keydown", function(e) {
            if (e.key === "Enter" || e.key === ",") {
                e.preventDefault();
                const username = this.value.trim();
                if (username) {
                    fetch(`get_users.php?username=${username}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.error) {
                                alert("User not found");
                            } else {
                                const user = data.user;
                                if (!selectedSharedUsers.some(u => u.user_id === user.user_id)) {
                                    selectedSharedUsers.push(user);
                                    addUserChip(user);
                                }
                            }
                        });
                }
                this.value = ""; // Clear input field
            }
        });

        function addUserChip(user) {
            const userList = document.getElementById("shared-users-list");
            const chip = document.createElement("span");
            chip.className = "shared-user-chip";
            chip.textContent = user.username;

            const removeButton = document.createElement("span");
            removeButton.className = "remove-user";
            removeButton.textContent = " ×";
            removeButton.onclick = function() {
                selectedSharedUsers = selectedSharedUsers.filter(u => u.user_id !== user.user_id);
                chip.remove();
            };

            chip.appendChild(removeButton);
            userList.appendChild(chip);
        }

        // Debugging: Check values before saving
        document.getElementById("save-event-btn").addEventListener("click", function() {
            console.log("Selected Tags Before Saving:", selectedTags);
            console.log("Selected Shared Users Before Saving:", selectedSharedUsers);
        });

        // Add this function to fetch all users
        function fetchUsers() {
            fetch('get_users.php')
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                    } else {
                        allUsers = data.users;
                    }
                })
                .catch(error => {
                    console.error('Error fetching users:', error);
                });
        }

        // Call this in your initialization
        if (isLoggedIn) {
            fetchUsers();
        }
        
        // Initialize the calendar
        renderCalendar();
        fetchEvents();
        
        // Event listeners for navigation
        prevMonthButton.addEventListener('click', function() {
            currentMonth = currentMonth.prevMonth();
            renderCalendar();
            fetchEvents();
        });
        
        nextMonthButton.addEventListener('click', function() {
            currentMonth = currentMonth.nextMonth();
            renderCalendar();
            fetchEvents();
        });
        
        // Add new event
        addEventButton.addEventListener('click', function() {
            openAddEventModal();
        });
        
        // Close modals
        closeModalButton.addEventListener('click', function() {
            eventModal.style.display = 'none';
        });
        
        closeDetailsModalButton.addEventListener('click', function() {
            eventDetailsModal.style.display = 'none';
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            if (event.target === eventModal) {
                eventModal.style.display = 'none';
            }
            if (event.target === eventDetailsModal) {
                eventDetailsModal.style.display = 'none';
            }
        });
        
        // Handle event form submission
        eventForm.addEventListener('submit', function(e) {
            e.preventDefault();
            saveEvent();
        });
        
        // Handle edit event from details modal
        editEventButton.addEventListener('click', function() {
            const eventId = editEventButton.getAttribute('data-id');
            const event = findEventById(eventId);
            if (event) {
                eventDetailsModal.style.display = 'none';
                openEditEventModal(event);
            }
        });
        
        // Handle event deletion from form
        deleteEventButton.addEventListener('click', function() {
            deleteEvent(eventIdInput.value);
        });
        
        // Handle event deletion from details
        deleteEventDetailsButton.addEventListener('click', function() {
            const eventId = editEventButton.getAttribute('data-id');
            deleteEvent(eventId);
        });
        
        // Fetch events for the current month (only if logged in)
        function fetchEvents() {
            if (!isLoggedIn) {
                // Skip fetching events for non-logged in users
                eventsData = {};
                renderEvents();
                return;
            }
            
            const monthIndex = currentMonth.month + 1; // Adjust to 1-indexed month for API
            let url = `get_events.php?month=${monthIndex}&year=${currentMonth.year}`;
            
            // Add tag filter if set
            if (currentTagFilter) {
                url += `&tag=${encodeURIComponent(currentTagFilter)}`;
            }
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                    } else {
                        console.log("API response:", data);
                        eventsData = data.events;
                        
                        // Update tag filter dropdown
                        updateTagFilterDropdown(data.calendar.all_tags, data.calendar.current_tag);
                        
                        renderEvents();
                    }
                })
                .catch(error => {
                    console.error('Error fetching events:', error);
                });
        }
        
        // Render the calendar structure
        function renderCalendar() {
            // Clear the calendar grid
            calendarGrid.innerHTML = '';
            
            // Set month title (convert from 0-indexed to name)
            const monthNames = ["January", "February", "March", "April", "May", "June",
                              "July", "August", "September", "October", "November", "December"];
            currentMonthElement.textContent = `${monthNames[currentMonth.month]} ${currentMonth.year}`;
            
            // Create header row with day names
            const dayHeaderRow = document.createElement('div');
            dayHeaderRow.className = 'week-row';
            
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            dayNames.forEach(day => {
                const dayHeader = document.createElement('div');
                dayHeader.className = 'day-header';
                dayHeader.textContent = day;
                dayHeaderRow.appendChild(dayHeader);
            });
            
            calendarGrid.appendChild(dayHeaderRow);
            
            // Get all weeks in the month
            const weeks = currentMonth.getWeeks();
            
            // Get current date for highlighting today
            const today = new Date();
            
            // Create a row for each week
            weeks.forEach(week => {
                const weekRow = document.createElement('div');
                weekRow.className = 'week-row';
                
                // Get the dates in this week
                const dates = week.getDates();
                
                // Create a cell for each day
                dates.forEach(date => {
                    const dayCell = document.createElement('div');
                    dayCell.className = 'calendar-day';
                    
                    // Check if the date is in the current month
                    if (date.getMonth() !== currentMonth.month) {
                        dayCell.classList.add('other-month');
                    }
                    
                    // Highlight today
                    if (date.getDate() === today.getDate() && 
                        date.getMonth() === today.getMonth() && 
                        date.getFullYear() === today.getFullYear()) {
                        dayCell.classList.add('today');
                    }
                    
                    // Add day number
                    const dayNumber = document.createElement('div');
                    dayNumber.className = 'day-number';
                    dayNumber.textContent = date.getDate();
                    dayCell.appendChild(dayNumber);
                    
                    // Add events container
                    const eventsList = document.createElement('div');
                    eventsList.className = 'events-list';
                    eventsList.id = `events-${date.getFullYear()}-${date.getMonth()}-${date.getDate()}`;
                    dayCell.appendChild(eventsList);
                    
                    // Add double-click to add event (only for logged-in users)
                    dayCell.addEventListener('dblclick', function() {
                        if (isLoggedIn) {
                            const dateStr = `${date.getFullYear()}-${(date.getMonth()+1).toString().padStart(2, '0')}-${date.getDate().toString().padStart(2, '0')}T12:00`;
                            openAddEventModal(dateStr);
                        } else {
                            alert('Please login or register to add events to the calendar.');
                        }
                    });
                    
                    weekRow.appendChild(dayCell);
                });
                
                calendarGrid.appendChild(weekRow);
            });
        }
        
        // Render events on the calendar
        function renderEvents() {
            // Clear all existing events first
            document.querySelectorAll('.events-list').forEach(list => {
                list.innerHTML = '';
            });
            
            // For each day with events
            for (const day in eventsData) {
                eventsData[day].forEach(event => {
                    // Parse the datetime
                    const eventDate = new Date(event.datetime);
                    
                    // Find the container for this date
                    const container = document.getElementById(`events-${eventDate.getFullYear()}-${eventDate.getMonth()}-${eventDate.getDate()}`);
                    if (container) {
                        // Create event element
                        const eventEl = document.createElement('div');
                        eventEl.className = 'event';
                        
                        // Add class for shared events
                        if (event.is_owner && event.shared_users && event.shared_users.length > 0) {
                            eventEl.classList.add('shared');
                        } else if (!event.is_owner) {
                            eventEl.classList.add('shared-with-me');
                        }
                        
                        // Add first tag as data attribute for styling
                        if (event.tags && event.tags.length > 0) {
                            eventEl.setAttribute('data-tag', event.tags[0]);
                        }
                        
                        eventEl.innerHTML = `
                            <span class="event-time">${event.time}</span>
                            <span class="event-title">${event.title}</span>
                        `;
                        
                        // Store event data
                        eventEl.setAttribute('data-id', event.id);
                        
                        // Add click event to show details
                        eventEl.addEventListener('click', function() {
                            openEventDetails(event);
                        });
                        
                        container.appendChild(eventEl);
                    }
                });
            }
        }
        
        // Find an event by ID
        function findEventById(eventId) {
            for (const day in eventsData) {
                for (const event of eventsData[day]) {
                    if (event.id == eventId) {
                        return event;
                    }
                }
            }
            return null;
        }
        
        // Open modal to add a new event
        function openAddEventModal(dateStr = '') {
            modalTitle.textContent = 'Add Event';
            eventIdInput.value = '';
            eventNameInput.value = '';
            eventDatetimeInput.value = dateStr;
            eventDescriptionInput.value = '';
            deleteEventButton.style.display = 'none';
            eventModal.style.display = 'block';
        }
        
        // Open modal to edit an existing event
        function openEditEventModal(event) {
            modalTitle.textContent = 'Edit Event';
            eventIdInput.value = event.id;
            eventNameInput.value = event.title;
            
            // Format the datetime for the input
            const eventDate = new Date(event.datetime);
            const formattedDate = eventDate.toISOString().slice(0, 16);
            eventDatetimeInput.value = formattedDate;
            
            eventDescriptionInput.value = event.description || '';
            
            // Clear and populate shared users
            selectedSharedUsers = [];
            document.getElementById('shared-users').value = '';
            document.getElementById('shared-users-list').innerHTML = '';
            
            if (event.shared_users && event.shared_users.length > 0) {
                event.shared_users.forEach(user => {
                    addSharedUserChip(user.user_id, user.username);
                });
            }
            
            // Clear and populate tags
            selectedTags = [];
            document.getElementById('event-tags').value = '';
            document.getElementById('event-tags-list').innerHTML = '';
            
            if (event.tags && event.tags.length > 0) {
                event.tags.forEach(tag => {
                    addTagChip(tag);
                    selectedTags.push(tag);
                });
            }
            
            deleteEventButton.style.display = 'block';
            eventModal.style.display = 'block';
        }
        
        // Open modal to view event details
        function openEventDetails(event) {
            eventDetailsTitle.textContent = event.title;
            
            // Format date for display
            const eventDate = new Date(event.datetime);
            const options = { 
                weekday: 'long', 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            };
            eventDetailsDatetime.textContent = eventDate.toLocaleDateString('en-US', options);
            
            // Show owner info
            document.getElementById('event-details-owner').textContent = 
                event.is_owner ? 'You created this event' : `Created by: ${event.owner_username}`;
            
            // Show tags
            const tagsContainer = document.getElementById('event-details-tags');
            tagsContainer.innerHTML = '';
            
            if (event.tags && event.tags.length > 0) {
                event.tags.forEach(tag => {
                    const tagChip = document.createElement('span');
                    tagChip.className = 'tag-chip';
                    tagChip.textContent = tag;
                    tagsContainer.appendChild(tagChip);
                });
            } else {
                tagsContainer.innerHTML = '<em>No tags</em>';
            }
            
            eventDetailsDescription.textContent = event.description || 'No description provided.';
            
            // Show shared users
            const sharedUsersContainer = document.getElementById('event-details-shared-users');
            sharedUsersContainer.innerHTML = '';
            
            if (event.shared_users && event.shared_users.length > 0) {
                event.shared_users.forEach(user => {
                    const userChip = document.createElement('span');
                    userChip.className = 'shared-user-chip';
                    userChip.textContent = user.username;
                    sharedUsersContainer.appendChild(userChip);
                });
            } else {
                sharedUsersContainer.textContent = 'Not shared with anyone';
            }
            
            // Show/hide sharing section
            document.getElementById('event-details-sharing').style.display = 
                event.is_owner ? 'block' : 'none';
            
            // Store event ID for edit/delete operations
            editEventButton.setAttribute('data-id', event.id);
            deleteEventDetailsButton.setAttribute('data-id', event.id);
            
            // Only show edit/delete buttons for events the user owns
            editEventButton.style.display = event.is_owner ? 'block' : 'none';
            deleteEventDetailsButton.style.display = event.is_owner ? 'block' : 'none';
            
            eventDetailsModal.style.display = 'block';
        }
        
        // Add these functions to handle shared users UI
        function setupSharedUsersInput() {
            const sharedUsersInput = document.getElementById('shared-users');
            const sharedUsersList = document.getElementById('shared-users-list');
            
            sharedUsersInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const value = this.value.trim();
                    if (value) {
                        // Check if username exists
                        const usernames = value.split(',').map(u => u.trim()).filter(u => u);
                        usernames.forEach(username => {
                            const user = allUsers.find(u => u.username.toLowerCase() === username.toLowerCase());
                            if (user) {
                                // Check if already added
                                if (!selectedSharedUsers.some(u => u.user_id === user.user_id)) {
                                    addSharedUserChip(user.user_id, user.username);
                                }
                            } else {
                                alert(`User "${username}" not found`);
                            }
                        });
                        this.value = '';
                    }
                }
            });
        }

        function addSharedUserChip(userId, username) {
            const sharedUsersList = document.getElementById('shared-users-list');
            
            // Add to selected users array
            selectedSharedUsers.push({ user_id: userId, username: username });
            
            // Create chip UI
            const chip = document.createElement('span');
            chip.className = 'shared-user-chip';
            chip.innerHTML = `
                ${username}
                <span class="remove-user" data-id="${userId}">&times;</span>
            `;
            
            // Add remove functionality
            chip.querySelector('.remove-user').addEventListener('click', function() {
                const userId = this.getAttribute('data-id');
                removeSharedUser(userId);
                chip.remove();
            });
            
            sharedUsersList.appendChild(chip);
        }

        function removeSharedUser(userId) {
            selectedSharedUsers = selectedSharedUsers.filter(user => user.user_id != userId);
        }
        
        // Update the saveEvent function to include shared users
        function saveEvent() {
            const eventId = eventIdInput.value;
            const eventData = {
                event_name: eventNameInput.value,
                event_datetime: eventDatetimeInput.value,
                event_description: eventDescriptionInput.value,
                shared_users: selectedSharedUsers.map(user => user.user_id),
                tags: selectedTags
            };
            
            console.log("Saving event with tags:", selectedTags);
            
            const url = eventId ? `update_event.php?id=${eventId}` : 'create_event.php';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(eventData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    eventModal.style.display = 'none';
                    fetchEvents(); // Refresh events
                }
            })
            .catch(error => {
                console.error('Error saving event:', error);
                alert('An error occurred while saving the event.');
            });
        }
        
        // Delete an event
        function deleteEvent(eventId) {
            if (!eventId) return;
            
            if (confirm('Are you sure you want to delete this event?')) {
                fetch(`delete_event.php?id=${eventId}`, {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                    } else {
                        eventModal.style.display = 'none';
                        eventDetailsModal.style.display = 'none';
                        fetchEvents(); // Refresh events
                    }
                })
                .catch(error => {
                    console.error('Error deleting event:', error);
                    alert('An error occurred while deleting the event.');
                });
            }
        }

        // Call this in your initialization
        setupSharedUsersInput();
        setupTagsInput();

        // Update tag filter dropdown
        function updateTagFilterDropdown(tags, currentTag) {
            const tagFilter = document.getElementById('tag-filter');
            
            // Save current selection
            const currentValue = tagFilter.value;
            
            // Clear existing options except the first one
            while (tagFilter.options.length > 1) {
                tagFilter.remove(1);
            }
            
            // Add tags to dropdown
            tags.forEach(tag => {
                const option = document.createElement('option');
                option.value = tag;
                option.textContent = tag;
                tagFilter.appendChild(option);
            });
            
            // Restore selection or set to current tag
            if (currentTag) {
                tagFilter.value = currentTag;
            } else if (currentValue) {
                tagFilter.value = currentValue;
            }
        }

        // Add tag filter change event
        document.getElementById('tag-filter').addEventListener('change', function() {
            currentTagFilter = this.value;
            fetchEvents();
        });

        // Add these functions to handle tags UI
        function setupTagsInput() {
            const tagsInput = document.getElementById('event-tags');
            const tagsList = document.getElementById('event-tags-list');
            
            tagsInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ',') {
                    e.preventDefault();
                    const value = this.value.trim();
                    if (value) {
                        // Split by comma and add each tag
                        const tags = value.split(',').map(t => t.trim()).filter(t => t);
                        tags.forEach(tag => {
                            // Check if already added
                            if (!selectedTags.includes(tag)) {
                                addTagChip(tag);
                            }
                        });
                        this.value = '';
                    }
                }
            });
        }

        // Make sure this function is actually adding tags to the array
        function addTagChip(tag) {
            const tagsList = document.getElementById('event-tags-list');
            
            // Add to selected tags array
            selectedTags.push(tag);
            
            // Create chip UI
            const chip = document.createElement('span');
            chip.className = 'tag-chip';
            chip.innerHTML = `
                ${tag}
                <span class="remove-tag" data-tag="${tag}">&times;</span>
            `;
            
            // Add remove functionality
            chip.querySelector('.remove-tag').addEventListener('click', function() {
                const tag = this.getAttribute('data-tag');
                removeTag(tag);
                chip.remove();
            });
            
            tagsList.appendChild(chip);
        }

        function removeTag(tag) {
            selectedTags = selectedTags.filter(t => t !== tag);
        }
    });
    </script>
</body>
</html>