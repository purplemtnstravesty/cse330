# core/serializers.py
from rest_framework import serializers
from django.contrib.auth import get_user_model
from .models import ProjectCard, HelperCard, Match

User = get_user_model()

class UserSerializer(serializers.ModelSerializer):
    class Meta:
        model = User
        fields = ('id', 'username', 'email', 'first_name', 'last_name', 'is_founder', 'is_helper')

class RegisterSerializer(serializers.ModelSerializer):
    password = serializers.CharField(write_only=True, required=True, style={'input_type': 'password'})
    # Role selection during registration
    role = serializers.ChoiceField(choices=[('founder', 'Founder'), ('helper', 'Helper')], write_only=True, required=True)

    class Meta:
        model = User
        fields = ('username', 'email', 'password', 'first_name', 'last_name', 'role')

    def create(self, validated_data):
        role = validated_data.pop('role')
        user = User.objects.create(
            username=validated_data['username'],
            email=validated_data.get('email', ''), # Make email optional if desired
            first_name=validated_data.get('first_name', ''),
            last_name=validated_data.get('last_name', '')
        )
        user.set_password(validated_data['password'])

        if role == 'founder':
            user.is_founder = True
            user.is_helper = False
        elif role == 'helper':
            user.is_helper = True
            user.is_founder = False

        user.save()
        return user

class ProjectCardSerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True) # Show nested user info on read
    user_id = serializers.PrimaryKeyRelatedField(
        queryset=User.objects.all(), source='user', write_only=True # Accept user ID on write
    )
    # Make skills/interests easier to handle as lists in frontend, convert back/forth
    skills_list = serializers.ListField(
        child=serializers.CharField(max_length=100), write_only=True, source='skills_needed'
    )
    interests_list = serializers.ListField(
        child=serializers.CharField(max_length=100), write_only=True, source='interests'
    )

    class Meta:
        model = ProjectCard
        fields = ('id', 'user', 'user_id', 'startup_name', 'role_title', 'description',
                  'skills_needed', 'interests', 'created_at', 'skills_list', 'interests_list')
        read_only_fields = ('user', 'created_at', 'skills_needed', 'interests') # User set in view, others auto/calculated

    def to_representation(self, instance):
        """Convert comma-separated strings back to lists for reading."""
        representation = super().to_representation(instance)
        representation['skills_needed'] = [s.strip() for s in instance.skills_needed.split(',') if s.strip()]
        representation['interests'] = [i.strip() for i in instance.interests.split(',') if i.strip()]
        return representation

    def create(self, validated_data):
        # Convert lists back to comma-separated strings before saving
        skills_list = validated_data.pop('skills_needed', [])
        interests_list = validated_data.pop('interests', [])
        validated_data['skills_needed'] = ','.join(skills_list)
        validated_data['interests'] = ','.join(interests_list)
        # user is set in the view using perform_create
        validated_data.pop('user', None) # Remove user from validated_data if present
        project_card = ProjectCard.objects.create(**validated_data)
        return project_card


class HelperCardSerializer(serializers.ModelSerializer):
    user = UserSerializer(read_only=True)
    user_id = serializers.PrimaryKeyRelatedField(
        queryset=User.objects.all(), source='user', write_only=True
    )
    skills_list = serializers.ListField(
        child=serializers.CharField(max_length=100), write_only=True, source='skills_offered'
    )
    interests_list = serializers.ListField(
        child=serializers.CharField(max_length=100), write_only=True, source='interests'
    )

    class Meta:
        model = HelperCard
        fields = ('id', 'user', 'user_id', 'skills_offered', 'interests', 'availability',
                  'created_at', 'skills_list', 'interests_list')
        read_only_fields = ('user', 'created_at', 'skills_offered', 'interests')

    def to_representation(self, instance):
        representation = super().to_representation(instance)
        representation['skills_offered'] = [s.strip() for s in instance.skills_offered.split(',') if s.strip()]
        representation['interests'] = [i.strip() for i in instance.interests.split(',') if i.strip()]
        return representation

    def create(self, validated_data):
        skills_list = validated_data.pop('skills_offered', [])
        interests_list = validated_data.pop('interests', [])
        validated_data['skills_offered'] = ','.join(skills_list)
        validated_data['interests'] = ','.join(interests_list)
        validated_data.pop('user', None)
        helper_card = HelperCard.objects.create(**validated_data)
        return helper_card


class MatchSerializer(serializers.ModelSerializer):
    # Use nested serializers for detailed match display
    project = ProjectCardSerializer(read_only=True)
    helper_card = HelperCardSerializer(read_only=True)
    helper_user = UserSerializer(source='helper_card.user', read_only=True) # Add helper user details

    class Meta:
        model = Match
        fields = ('id', 'project', 'helper_card', 'helper_user', 'score', 'created_at')