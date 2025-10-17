import os
from pydantic import BaseSettings
from typing import Optional

class Settings(BaseSettings):
    # Application
    APP_NAME: str = "DO4ME Payment API"
    APP_VERSION: str = "1.0.0"
    DEBUG: bool = False
    
    # Database
    DATABASE_URL: str = os.getenv("DATABASE_URL", "mysql://user:pass@localhost/do4me_payments")
    
    # Payment Gateways
    STRIPE_SECRET_KEY: Optional[str] = None
    STRIPE_PUBLISHABLE_KEY: Optional[str] = None
    STRIPE_WEBHOOK_SECRET: Optional[str] = None
    
    MPESA_CONSUMER_KEY: Optional[str] = None
    MPESA_CONSUMER_SECRET: Optional[str] = None
    MPESA_SHORTCODE: Optional[str] = None
    MPESA_PASSKEY: Optional[str] = None
    
    PAYPAL_CLIENT_ID: Optional[str] = None
    PAYPAL_CLIENT_SECRET: Optional[str] = None
    PAYPAL_MODE: str = "sandbox"  # or "live"
    
    FLUTTERWAVE_PUBLIC_KEY: Optional[str] = None
    FLUTTERWAVE_SECRET_KEY: Optional[str] = None
    
    # Security
    API_KEY: str = os.getenv("PAYMENT_API_KEY", "default-secret-key")
    JWT_SECRET: str = os.getenv("JWT_SECRET", "jwt-secret-key")
    ALGORITHM: str = "HS256"
    
    # CORS
    ALLOWED_ORIGINS: list = ["http://localhost:80", "https://do4me.com"]
    
    class Config:
        env_file = ".env"

settings = Settings()