# core/urls.py
from django.urls import path
from . import views

urlpatterns = [
    path('register/', views.RegisterView.as_view(), name='register'),
    path('user/', views.UserDetailView.as_view(), name='user-detail'), # Endpoint to get current user info

    path('projects/', views.ProjectCardListCreateView.as_view(), name='project-list-create'),
    path('projects/<int:pk>/', views.ProjectCardDetailView.as_view(), name='project-detail'),

    path('helpers/', views.HelperCardListCreateView.as_view(), name='helper-list-create'),
    path('helpers/<int:pk>/', views.HelperCardDetailView.as_view(), name='helper-detail'),

    path('matches/', views.MatchListView.as_view(), name='match-list'),
]