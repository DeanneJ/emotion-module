"""
Compatibility stub module for loading legacy models.
This module redirects 'src' references to current 'app' structure.
"""

# Import from app to make this module work as a bridge
from app import models
