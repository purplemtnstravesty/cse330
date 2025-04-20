# core/views.py
from django.shortcuts import render
from django.contrib.auth import get_user_model
from rest_framework import generics, permissions, status
from rest_framework.response import Response
from .models import ProjectCard, HelperCard, Match
from .serializers import (
    UserSerializer, RegisterSerializer,
    ProjectCardSerializer, HelperCardSerializer, MatchSerializer
)
from .permissions import IsFounder, IsHelper, IsOwnerOrReadOnly

User = get_user_model()

class RegisterView(generics.CreateAPIView):
    queryset = User.objects.all()
    permission_classes = (permissions.AllowAny,) # Anyone can register
    serializer_class = RegisterSerializer

class UserDetailView(generics.RetrieveAPIView):
    """ Get current user details """
    serializer_class = UserSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_object(self):
        return self.request.user

class ProjectCardListCreateView(generics.ListCreateAPIView):
    serializer_class = ProjectCardSerializer
    permission_classes = [permissions.IsAuthenticated] # Must be logged in

    def get_queryset(self):
        # Users can see all projects, but maybe filter later?
        return ProjectCard.objects.all().order_by('-created_at')

    def perform_create(self, serializer):
        # Only allow founders to create projects
        if not self.request.user.is_founder:
            # Although permission_classes check this, explicit check adds clarity
             raise PermissionDenied("Only founders can post projects.")
        # Automatically associate the project with the logged-in user
        serializer.save(user=self.request.user)

    def get_permissions(self):
        """
        Instantiates and returns the list of permissions that this view requires.
        Allow any authenticated user to view (GET), but only founders to create (POST).
        """
        if self.request.method == 'POST':
            # Use IsFounder permission only for POST requests
            return [permissions.IsAuthenticated(), IsFounder()]
        # For GET requests, just require authentication
        return [permissions.IsAuthenticated()]


class ProjectCardDetailView(generics.RetrieveUpdateDestroyAPIView):
    queryset = ProjectCard.objects.all()
    serializer_class = ProjectCardSerializer
    # Allow owner to edit/delete, others read-only
    permission_classes = [permissions.IsAuthenticated, IsOwnerOrReadOnly]


class HelperCardListCreateView(generics.ListCreateAPIView):
    serializer_class = HelperCardSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        # Users can see all helper cards, maybe filter later?
        return HelperCard.objects.all().order_by('-created_at')

    def perform_create(self, serializer):
         # Only allow helpers to create helper cards
        if not self.request.user.is_helper:
            raise PermissionDenied("Only helpers can post helper cards.")
        serializer.save(user=self.request.user)

    def get_permissions(self):
        """ Allow any authenticated user to view (GET), but only helpers to create (POST). """
        if self.request.method == 'POST':
            return [permissions.IsAuthenticated(), IsHelper()]
        return [permissions.IsAuthenticated()]

class HelperCardDetailView(generics.RetrieveUpdateDestroyAPIView):
    queryset = HelperCard.objects.all()
    serializer_class = HelperCardSerializer
    permission_classes = [permissions.IsAuthenticated, IsOwnerOrReadOnly]


class MatchListView(generics.ListAPIView):
    serializer_class = MatchSerializer
    permission_classes = [permissions.IsAuthenticated]

    def get_queryset(self):
        """
        Return matches relevant to the logged-in user.
        Founders see matches for their projects.
        Helpers see matches where they are the helper.
        """
        user = self.request.user
        if user.is_founder:
            # Get matches for projects owned by this founder
            return Match.objects.filter(project__user=user).order_by('-score', '-created_at')
        elif user.is_helper:
            # Get matches where this user's helper card is involved
            # Ensure user has a helper card first
            try:
                helper_card = user.helper_cards.latest('created_at') # Or get a specific one if multiple allowed
                return Match.objects.filter(helper_card=helper_card).order_by('-score', '-created_at')
            except HelperCard.DoesNotExist:
                return Match.objects.none() # Return empty if user has no helper card
        else:
            # Should not happen if authenticated, but handle anyway
            return Match.objects.none()