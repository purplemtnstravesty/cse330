# projects/apps.py
from django.apps import AppConfig

class CoreConfig(AppConfig):
    default_auto_field = 'django.db.models.BigAutoField'
    name = 'core'

    def ready(self):
        # Import signals here to ensure they are registered when the app is ready
        import core.signals
        print("Projects signals registered.")