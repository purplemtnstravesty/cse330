# core/models.py
from django.db import models
from django.contrib.auth.models import AbstractUser
from django.conf import settings

class User(AbstractUser):
    # Inherits username, first_name, last_name, email, password, is_staff, is_active, date_joined etc.
    is_founder = models.BooleanField(default=False)
    is_helper = models.BooleanField(default=False)

    def __str__(self):
        return self.username

class ProjectCard(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='project_cards')
    startup_name = models.CharField(max_length=200)
    role_title = models.CharField(max_length=200)
    description = models.TextField()
    skills_needed = models.TextField(help_text="Comma-separated list of skills") # Store as text, parse in logic
    interests = models.TextField(help_text="Comma-separated list of interests") # Store as text, parse in logic
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"{self.role_title} at {self.startup_name}"

    def get_skills_set(self):
        return set(skill.strip().lower() for skill in self.skills_needed.split(',') if skill.strip())

    def get_interests_set(self):
        return set(interest.strip().lower() for interest in self.interests.split(',') if interest.strip())

class HelperCard(models.Model):
    user = models.ForeignKey(settings.AUTH_USER_MODEL, on_delete=models.CASCADE, related_name='helper_cards')
    skills_offered = models.TextField(help_text="Comma-separated list of skills") # Store as text, parse in logic
    interests = models.TextField(help_text="Comma-separated list of interests") # Store as text, parse in logic
    availability = models.CharField(max_length=200, blank=True) # E.g., "5-10 hrs/week", "Weekends"
    created_at = models.DateTimeField(auto_now_add=True)

    def __str__(self):
        return f"Helper profile for {self.user.username}"

    def get_skills_set(self):
        return set(skill.strip().lower() for skill in self.skills_offered.split(',') if skill.strip())

    def get_interests_set(self):
        return set(interest.strip().lower() for interest in self.interests.split(',') if interest.strip())

class Match(models.Model):
    project = models.ForeignKey(ProjectCard, on_delete=models.CASCADE, related_name='matches')
    helper_card = models.ForeignKey(HelperCard, on_delete=models.CASCADE, related_name='matches') # Renamed from 'helper' to avoid clash
    score = models.FloatField(default=0.0) # Similarity score
    created_at = models.DateTimeField(auto_now_add=True)

    class Meta:
        unique_together = ('project', 'helper_card') # Ensure only one match record per pair
        verbose_name_plural = "Matches"

    def __str__(self):
        return f"Match: {self.project.role_title} <> {self.helper_card.user.username} (Score: {self.score:.2f})"