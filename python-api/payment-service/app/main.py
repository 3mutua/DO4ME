"""
DO4ME Payment Service - FastAPI Application
Main entry point for the payment microservice
"""

from fastapi import FastAPI, HTTPException, Depends, status
from fastapi.middleware.cors import CORSMiddleware
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from fastapi.responses import JSONResponse
from fastapi.encoders import jsonable_encoder
from contextlib import asynccontextmanager
import uvicorn
import logging
from typing import Optional, Dict, Any

from .core.config import settings
from .core.security import verify_api_key
from .core.database import init_db, close_db
from .core.exceptions import PaymentError, ValidationError
from .services.stripe_service import StripeService
from .services.mpesa_service import MpesaService
from .services.paypal_service import PayPalService
from .models.payment_models import (
    PaymentIntentRequest,
    PaymentIntentResponse,
    PaymentConfirmationRequest,
    WebhookRequest,
    PayoutRequest,
    PayoutResponse
)

# Configure logging
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s - %(name)s - %(levelname)s - %(message)s',
    handlers=[
        logging.FileHandler('payment_service.log'),
        logging.StreamHandler()
    ]
)

logger = logging.getLogger(__name__)

# Security scheme
security = HTTPBearer()

@asynccontextmanager
async def lifespan(app: FastAPI):
    """Application lifespan context manager"""
    # Startup
    logger.info("Starting DO4ME Payment Service...")
    await init_db()
    logger.info("Payment Service started successfully")
    
    yield
    
    # Shutdown
    logger.info("Shutting down Payment Service...")
    await close_db()
    logger.info("Payment Service shutdown complete")

# Create FastAPI application
app = FastAPI(
    title="DO4ME Payment Service",
    description="Microservice for handling payments and payouts",
    version="1.0.0",
    docs_url="/docs" if settings.DEBUG else None,
    redoc_url="/redoc" if settings.DEBUG else None,
    lifespan=lifespan
)

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.ALLOWED_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Global exception handlers
@app.exception_handler(PaymentError)
async def payment_error_handler(request, exc: PaymentError):
    logger.error(f"Payment error: {exc.detail}")
    return JSONResponse(
        status_code=status.HTTP_400_BAD_REQUEST,
        content={"success": False, "error": exc.detail}
    )

@app.exception_handler(ValidationError)
async def validation_error_handler(request, exc: ValidationError):
    logger.error(f"Validation error: {exc.detail}")
    return JSONResponse(
        status_code=status.HTTP_422_UNPROCESSABLE_ENTITY,
        content={"success": False, "error": exc.detail}
    )

@app.exception_handler(HTTPException)
async def http_exception_handler(request, exc: HTTPException):
    logger.error(f"HTTP error: {exc.detail}")
    return JSONResponse(
        status_code=exc.status_code,
        content={"success": False, "error": exc.detail}
    )

@app.exception_handler(Exception)
async def general_exception_handler(request, exc: Exception):
    logger.error(f"Unexpected error: {str(exc)}", exc_info=True)
    return JSONResponse(
        status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
        content={"success": False, "error": "Internal server error"}
    )

# Health check endpoint
@app.get("/health")
async def health_check():
    """Health check endpoint"""
    return {
        "status": "healthy",
        "service": "payment-service",
        "timestamp": datetime.utcnow().isoformat()
    }

@app.get("/")
async def root():
    """Root endpoint"""
    return {
        "message": "DO4ME Payment Service",
        "version": "1.0.0",
        "status": "running"
    }

# Payment endpoints
@app.post("/api/v1/payments/create-intent", response_model=PaymentIntentResponse)
async def create_payment_intent(
    request: PaymentIntentRequest,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Create a payment intent for processing payments
    
    Args:
        request: Payment intent request data
        credentials: API credentials
        
    Returns:
        Payment intent response
    """
    # Verify API key
    await verify_api_key(credentials.credentials)
    
    try:
        # Select payment service based on method
        if request.payment_method == "stripe":
            service = StripeService()
            result = await service.create_payment_intent(
                amount=int(request.amount * 100),  # Convert to cents
                currency=request.currency,
                metadata=request.metadata
            )
        elif request.payment_method == "mpesa":
            service = MpesaService()
            result = await service.initiate_stk_push(
                amount=request.amount,
                phone_number=request.metadata.get('phone_number'),
                user_id=request.user_id
            )
        elif request.payment_method == "paypal":
            service = PayPalService()
            result = await service.create_order(
                amount=request.amount,
                currency=request.currency,
                metadata=request.metadata
            )
        else:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Unsupported payment method"
            )
        
        # Save payment record to database (pseudo-code)
        # await save_payment_record(request.user_id, result, request.payment_method)
        
        logger.info(f"Created payment intent for user {request.user_id}: {result['payment_intent_id']}")
        
        return PaymentIntentResponse(**result)
        
    except PaymentError as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))
    except Exception as e:
        logger.error(f"Unexpected error creating payment intent: {str(e)}")
        raise HTTPException(status_code=status.HTTP_500_INTERNAL_SERVER_ERROR, detail="Internal server error")

@app.post("/api/v1/payments/confirm")
async def confirm_payment(
    request: PaymentConfirmationRequest,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Confirm a payment intent
    
    Args:
        request: Payment confirmation request
        credentials: API credentials
        
    Returns:
        Confirmation result
    """
    await verify_api_key(credentials.credentials)
    
    try:
        # Verify payment with appropriate service
        # This is a simplified implementation
        stripe_service = StripeService()
        payment_status = await stripe_service.confirm_payment_intent(request.payment_intent_id)
        
        if payment_status['status'] == 'succeeded':
            # Update payment status in database
            # await update_payment_status(request.payment_intent_id, 'completed')
            
            # Update user wallet balance
            # await update_wallet_balance(request.user_id, payment_amount)
            
            logger.info(f"Payment confirmed: {request.payment_intent_id}")
        
        return {
            "success": True,
            "payment_status": payment_status['status'],
            "payment_intent_id": request.payment_intent_id
        }
        
    except PaymentError as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))

@app.post("/api/v1/payments/webhook/stripe")
async def stripe_webhook(webhook_request: WebhookRequest):
    """
    Handle Stripe webhook events
    
    Args:
        webhook_request: Webhook request data
        
    Returns:
        Webhook processing result
    """
    try:
        stripe_service = StripeService()
        result = await stripe_service.handle_webhook(
            webhook_request.payload,
            webhook_request.signature
        )
        
        return {"success": True, "result": result}
        
    except (ValidationError, PaymentError) as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))

@app.post("/api/v1/payments/webhook/mpesa")
async def mpesa_webhook(webhook_request: WebhookRequest):
    """
    Handle M-Pesa webhook events
    
    Args:
        webhook_request: Webhook request data
        
    Returns:
        Webhook processing result
    """
    try:
        mpesa_service = MpesaService()
        result = await mpesa_service.handle_webhook(webhook_request.payload)
        
        return {"success": True, "result": result}
        
    except (ValidationError, PaymentError) as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))

@app.post("/api/v1/payouts", response_model=PayoutResponse)
async def create_payout(
    request: PayoutRequest,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Create a payout to a user
    
    Args:
        request: Payout request data
        credentials: API credentials
        
    Returns:
        Payout response
    """
    await verify_api_key(credentials.credentials)
    
    try:
        stripe_service = StripeService()
        result = await stripe_service.create_payout(
            amount=int(request.amount * 100),  # Convert to cents
            currency=request.currency,
            destination=request.destination_account,
            description=request.description
        )
        
        # Save payout record to database
        # await save_payout_record(request.user_id, result)
        
        logger.info(f"Created payout for user {request.user_id}: {result['payout_id']}")
        
        return PayoutResponse(**result)
        
    except PaymentError as e:
        raise HTTPException(status_code=status.HTTP_400_BAD_REQUEST, detail=str(e))

@app.get("/api/v1/payments/{payment_intent_id}")
async def get_payment_status(
    payment_intent_id: str,
    credentials: HTTPAuthorizationCredentials = Depends(security)
):
    """
    Get payment status by payment intent ID
    
    Args:
        payment_intent_id: Stripe payment intent ID
        credentials: API credentials
        
    Returns:
        Payment status information
    """
    await verify_api_key(credentials.credentials)
    
    try:
        stripe_service = StripeService()
        payment_info = await stripe_service.get_payment_intent(payment_intent_id)
        
        return {
            "success": True,
            "payment": payment_info
        }
        
    except PaymentError as e:
        raise HTTPException(status_code=status.HTTP_404_NOT_FOUND, detail=str(e))

@app.get("/api/v1/config")
async def get_payment_config(credentials: HTTPAuthorizationCredentials = Depends(security)):
    """
    Get payment service configuration
    
    Args:
        credentials: API credentials
        
    Returns:
        Payment configuration
    """
    await verify_api_key(credentials.credentials)
    
    stripe_service = StripeService()
    
    return {
        "success": True,
        "config": {
            "stripe": {
                "publishable_key": stripe_service.get_publishable_key(),
                "currencies": ["usd", "eur", "gbp"]
            },
            "mpesa": {
                "currencies": ["kes"],
                "countries": ["KE"]
            },
            "paypal": {
                "currencies": ["usd", "eur"],
                "mode": settings.PAYPAL_MODE
            }
        }
    }

# Utility function to save payment record (pseudo-implementation)
async def save_payment_record(user_id: int, payment_data: Dict, method: str) -> None:
    """Save payment record to database"""
    # This would typically interact with a database
    # For now, we'll just log it
    logger.info(f"Saved payment record for user {user_id}: {payment_data}")

async def update_payment_status(payment_intent_id: str, status: str) -> None:
    """Update payment status in database"""
    logger.info(f"Updated payment {payment_intent_id} to status: {status}")

async def update_wallet_balance(user_id: int, amount: float) -> None:
    """Update user wallet balance"""
    logger.info(f"Updated wallet for user {user_id} with amount: {amount}")

async def save_payout_record(user_id: int, payout_data: Dict) -> None:
    """Save payout record to database"""
    logger.info(f"Saved payout record for user {user_id}: {payout_data}")

# Run the application
if __name__ == "__main__":
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=8000,
        reload=settings.DEBUG,
        log_level="info"
    )