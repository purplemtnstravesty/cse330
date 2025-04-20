# core/matching.py
from .models import ProjectCard, HelperCard, Match

def calculate_jaccard_similarity(set1, set2):
    """Calculates Jaccard similarity between two sets."""
    intersection = len(set1.intersection(set2))
    union = len(set1.union(set2))
    return intersection / union if union > 0 else 0.0

def calculate_match_score(project_card, helper_card):
    """Calculates a simple match score based on skills and interests overlap."""
    project_skills = project_card.get_skills_set()
    helper_skills = helper_card.get_skills_set()
    project_interests = project_card.get_interests_set()
    helper_interests = helper_card.get_interests_set()

    # Basic scoring: weighted average of skill and interest similarity
    # Adjust weights as needed
    skill_similarity = calculate_jaccard_similarity(project_skills, helper_skills)
    interest_similarity = calculate_jaccard_similarity(project_interests, helper_interests)

    # Example: 60% weight for skills, 40% for interests
    score = (0.6 * skill_similarity) + (0.4 * interest_similarity)

    # Add more complex logic here: e.g., keyword matching, weighting specific skills, etc.

    return score

def run_matching_for_project(project_card):
    """Finds and saves matches for a given project card."""
    print(f"Running matching for project: {project_card.id} - {project_card.role_title}")
    helper_cards = HelperCard.objects.all() # Consider filtering if needed
    matches_created_or_updated = 0

    for helper_card in helper_cards:
        score = calculate_match_score(project_card, helper_card)
        threshold = 0.1 # Only create matches above a certain threshold score

        if score >= threshold:
            match, created = Match.objects.update_or_create(
                project=project_card,
                helper_card=helper_card,
                defaults={'score': score}
            )
            matches_created_or_updated += 1
            # print(f"  {'Created' if created else 'Updated'} match with {helper_card.user.username}, Score: {score:.2f}")
        else:
             # Optional: Delete existing match if score drops below threshold
            Match.objects.filter(project=project_card, helper_card=helper_card).delete()

    print(f"  Finished matching for project {project_card.id}. {matches_created_or_updated} matches created/updated.")


def run_matching_for_helper(helper_card):
    """Finds and saves matches for a given helper card."""
    print(f"Running matching for helper: {helper_card.id} - {helper_card.user.username}")
    project_cards = ProjectCard.objects.all() # Consider filtering if needed
    matches_created_or_updated = 0

    for project_card in project_cards:
        score = calculate_match_score(project_card, helper_card)
        threshold = 0.1 # Only create matches above a certain threshold score

        if score >= threshold:
            match, created = Match.objects.update_or_create(
                project=project_card,
                helper_card=helper_card,
                defaults={'score': score}
            )
            matches_created_or_updated += 1
            # print(f"  {'Created' if created else 'Updated'} match with {project_card.role_title}, Score: {score:.2f}")
        else:
            # Optional: Delete existing match if score drops below threshold
            Match.objects.filter(project=project_card, helper_card=helper_card).delete()

    print(f"  Finished matching for helper {helper_card.id}. {matches_created_or_updated} matches created/updated.")