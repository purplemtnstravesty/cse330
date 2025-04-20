# core/signals.py
from django.db.models.signals import post_save
from django.dispatch import receiver
from .models import ProjectCard, HelperCard
from .matching import run_matching_for_project, run_matching_for_helper

@receiver(post_save, sender=ProjectCard)
def project_card_saved(sender, instance, created, **kwargs):
    """Trigger matching logic when a ProjectCard is saved."""
    print(f"Signal received: ProjectCard saved (ID: {instance.id}, Created: {created})")
    run_matching_for_project(instance)

@receiver(post_save, sender=HelperCard)
def helper_card_saved(sender, instance, created, **kwargs):
    """Trigger matching logic when a HelperCard is saved."""
    print(f"Signal received: HelperCard saved (ID: {instance.id}, Created: {created})")
    run_matching_for_helper(instance)