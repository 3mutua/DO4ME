"""
Stripe Payment Service
Handles Stripe payment processing, webhooks, and customer management
"""

import stripe
import logging
from typing import Dict, Any, Optional
from datetime import datetime
from ..core.config import settings
from ..core.exceptions import PaymentError, ValidationError

logger = logging.getLogger(__name__)

class StripeService:
    """Service for handling Stripe payment operations"""
    
    def __init__(self):
        self.stripe = stripe
        self.stripe.api_key = settings.STRIPE_SECRET_KEY
        
        # Configure Stripe version
        self.stripe.max_network_retries = 2
        self.stripe.default_http_client = stripe.http_client.RequestsClient()
    
    async def create_payment_intent(
        self, 
        amount: int, 
        currency: str = "usd", 
        metadata: Optional[Dict] = None,
        customer_id: Optional[str] = None
    ) -> Dict[str, Any]:
        """
        Create a Stripe Payment Intent
        
        Args:
            amount: Amount in smallest currency unit (e.g., cents for USD)
            currency: Currency code (default: "usd")
            metadata: Additional metadata for the payment
            customer_id: Stripe customer ID for recurring payments
            
        Returns:
            Payment Intent object
        """
        try:
            intent_data = {
                "amount": amount,
                "currency": currency,
                "automatic_payment_methods": {"enabled": True},
                "metadata": metadata or {}
            }
            
            if customer_id:
                intent_data["customer"] = customer_id
            else:
                # Create a new customer for this payment
                customer = await self.create_customer(metadata)
                intent_data["customer"] = customer.id
            
            payment_intent = self.stripe.PaymentIntent.create(**intent_data)
            
            logger.info(f"Created payment intent: {payment_intent.id}")
            
            return {
                "payment_intent_id": payment_intent.id,
                "client_secret": payment_intent.client_secret,
                "status": payment_intent.status,
                "amount": payment_intent.amount,
                "currency": payment_intent.currency
            }
            
        except stripe.error.StripeError as e:
            logger.error(f"Stripe error creating payment intent: {str(e)}")
            raise PaymentError(f"Failed to create payment intent: {str(e)}")
        except Exception as e:
            logger.error(f"Unexpected error creating payment intent: {str(e)}")
            raise PaymentError("Internal server error")
    
    async def confirm_payment_intent(self, payment_intent_id: str) -> Dict[str, Any]:
        """
        Confirm a Payment Intent
        
        Args:
            payment_intent_id: Stripe Payment Intent ID
            
        Returns:
            Updated Payment Intent object
        """
        try:
            payment_intent = self.stripe.PaymentIntent.retrieve(payment_intent_id)
            
            if payment_intent.status == "succeeded":
                return {
                    "payment_intent_id": payment_intent.id,
                    "status": payment_intent.status,
                    "amount": payment_intent.amount,
                    "currency": payment_intent.currency
                }
            
            # Confirm the payment intent
            confirmed_intent = self.stripe.PaymentIntent.confirm(payment_intent_id)
            
            logger.info(f"Confirmed payment intent: {payment_intent_id}")
            
            return {
                "payment_intent_id": confirmed_intent.id,
                "status": confirmed_intent.status,
                "amount": confirmed_intent.amount,
                "currency": confirmed_intent.currency
            }
            
        except stripe.error.StripeError as e:
            logger.error(f"Stripe error confirming payment intent: {str(e)}")
            raise PaymentError(f"Failed to confirm payment: {str(e)}")
    
    async def get_payment_intent(self, payment_intent_id: str) -> Dict[str, Any]:
        """
        Retrieve Payment Intent details
        
        Args:
            payment_intent_id: Stripe Payment Intent ID
            
        Returns:
            Payment Intent object
        """
        try:
            payment_intent = self.stripe.PaymentIntent.retrieve(payment_intent_id)
            
            return {
                "payment_intent_id": payment_intent.id,
                "status": payment_intent.status,
                "amount": payment_intent.amount,
                "currency": payment_intent.currency,
                "customer": payment_intent.customer,
                "created": datetime.fromtimestamp(payment_intent.created),
                "charges": [
                    {
                        "charge_id": charge.id,
                        "amount": charge.amount,
                        "status": charge.status
                    }
                    for charge in payment_intent.charges.data
                ] if payment_intent.charges else []
            }
            
        except stripe.error.StripeError as e:
            logger.error(f"Stripe error retrieving payment intent: {str(e)}")
            raise PaymentError(f"Payment not found: {str(e)}")
    
    async def create_customer(self, metadata: Optional[Dict] = None) -> stripe.Customer:
        """
        Create a Stripe customer
        
        Args:
            metadata: Customer metadata
            
        Returns:
            Stripe Customer object
        """
        try:
            customer_data = {
                "description": "DO4ME Platform Customer",
                "metadata": metadata or {}
            }
            
            customer = self.stripe.Customer.create(**customer_data)
            logger.info(f"Created Stripe customer: {customer.id}")
            
            return customer
            
        except stripe.error.StripeError as e:
            logger.error(f"Stripe error creating customer: {str(e)}")
            raise PaymentError(f"Failed to create customer: {str(e)}")
    
    async def create_setup_intent(self, customer_id: str) -> Dict[str, Any]:
        """
        Create a Setup Intent for saving payment methods
        
        Args:
            customer_id: Stripe customer ID
            
        Returns:
            Setup Intent object
        """
        try:
            setup_intent = self.stripe.SetupIntent.create(
                customer=customer_id,
                payment_method_types=["card"]
            )
            
            logger.info(f"Created setup intent: {setup_intent.id}")
            
            return {
                "setup_intent_id": setup_intent.id,
                "client_secret": setup_intent.client_secret,
                "status": setup_intent.status
            }
            
        except stripe.error.StripeError as e:
            logger.error(f"Stripe error creating setup intent: {str(e)}")
            raise PaymentError(f"Failed to create setup intent: {str(e)}")
    
    async def create_payout(
        self, 
        amount: int, 
        currency: str, 
        destination: str,
        description: str = ""
    ) -> Dict[str, Any]:
        """
        Create a payout to a connected account or bank
        
        Args:
            amount: Amount in smallest currency unit
            currency: Currency code
            destination: Destination account ID
            description: Payout description
            
        Returns:
            Payout object
        """
        try:
            payout = self.stripe.Payout.create(
                amount=amount,
                currency=currency,
                destination=destination,
                description=description or f"DO4ME Payout - {datetime.utcnow().isoformat()}"
            )
            
            logger.info(f"Created payout: {payout.id}")
            
            return {
                "payout_id": payout.id,
                "amount": payout.amount,
                "currency": payout.currency,
                "status": payout.status,
                "arrival_date": datetime.fromtimestamp(payout.arrival_date)
            }
            
        except stripe.error.StripeError as e:
            logger.error(f"Stripe error creating payout: {str(e)}")
            raise PaymentError(f"Failed to create payout: {str(e)}")
    
    async def handle_webhook(self, payload: bytes, sig_header: str) -> Dict[str, Any]:
        """
        Handle Stripe webhook events
        
        Args:
            payload: Raw webhook payload
            sig_header: Stripe signature header
            
        Returns:
            Webhook processing result
        """
        try:
            # Verify webhook signature
            event = self.stripe.Webhook.construct_event(
                payload, sig_header, settings.STRIPE_WEBHOOK_SECRET
            )
            
            logger.info(f"Received Stripe webhook: {event['type']}")
            
            # Process the event
            if event['type'] == 'payment_intent.succeeded':
                return await self._handle_payment_success(event['data']['object'])
            elif event['type'] == 'payment_intent.payment_failed':
                return await self._handle_payment_failure(event['data']['object'])
            elif event['type'] == 'charge.succeeded':
                return await self._handle_charge_success(event['data']['object'])
            elif event['type'] == 'charge.failed':
                return await self._handle_charge_failure(event['data']['object'])
            elif event['type'] == 'payout.paid':
                return await self._handle_payout_paid(event['data']['object'])
            elif event['type'] == 'payout.failed':
                return await self._handle_payout_failed(event['data']['object'])
            else:
                logger.info(f"Unhandled event type: {event['type']}")
                return {"status": "ignored", "event_type": event['type']}
                
        except ValueError as e:
            logger.error(f"Invalid webhook payload: {str(e)}")
            raise ValidationError("Invalid payload")
        except stripe.error.SignatureVerificationError as e:
            logger.error(f"Invalid webhook signature: {str(e)}")
            raise ValidationError("Invalid signature")
        except Exception as e:
            logger.error(f"Webhook processing error: {str(e)}")
            raise PaymentError("Webhook processing failed")
    
    async def _handle_payment_success(self, payment_intent: Dict) -> Dict[str, Any]:
        """Handle successful payment intent"""
        try:
            # Update database record
            # This would typically call a database service
            payment_data = {
                "payment_intent_id": payment_intent['id'],
                "status": "completed",
                "amount": payment_intent['amount'],
                "currency": payment_intent['currency'],
                "customer_id": payment_intent.get('customer'),
                "metadata": payment_intent.get('metadata', {})
            }
            
            logger.info(f"Payment succeeded: {payment_intent['id']}")
            
            # Notify other services (e.g., update user wallet)
            await self._notify_payment_success(payment_data)
            
            return {
                "status": "processed",
                "event": "payment_intent.succeeded",
                "payment_intent_id": payment_intent['id']
            }
            
        except Exception as e:
            logger.error(f"Error handling payment success: {str(e)}")
            raise
    
    async def _handle_payment_failure(self, payment_intent: Dict) -> Dict[str, Any]:
        """Handle failed payment intent"""
        try:
            payment_data = {
                "payment_intent_id": payment_intent['id'],
                "status": "failed",
                "error": payment_intent.get('last_payment_error', {}),
                "metadata": payment_intent.get('metadata', {})
            }
            
            logger.warning(f"Payment failed: {payment_intent['id']}")
            
            # Notify user about failed payment
            await self._notify_payment_failure(payment_data)
            
            return {
                "status": "processed",
                "event": "payment_intent.payment_failed",
                "payment_intent_id": payment_intent['id']
            }
            
        except Exception as e:
            logger.error(f"Error handling payment failure: {str(e)}")
            raise
    
    async def _handle_charge_success(self, charge: Dict) -> Dict[str, Any]:
        """Handle successful charge"""
        logger.info(f"Charge succeeded: {charge['id']}")
        return {
            "status": "processed",
            "event": "charge.succeeded",
            "charge_id": charge['id']
        }
    
    async def _handle_charge_failure(self, charge: Dict) -> Dict[str, Any]:
        """Handle failed charge"""
        logger.warning(f"Charge failed: {charge['id']}")
        return {
            "status": "processed",
            "event": "charge.failed",
            "charge_id": charge['id']
        }
    
    async def _handle_payout_paid(self, payout: Dict) -> Dict[str, Any]:
        """Handle paid payout"""
        logger.info(f"Payout paid: {payout['id']}")
        
        # Update payout status in database
        await self._update_payout_status(payout['id'], 'paid')
        
        return {
            "status": "processed",
            "event": "payout.paid",
            "payout_id": payout['id']
        }
    
    async def _handle_payout_failed(self, payout: Dict) -> Dict[str, Any]:
        """Handle failed payout"""
        logger.error(f"Payout failed: {payout['id']}")
        
        # Update payout status and notify admin
        await self._update_payout_status(payout['id'], 'failed')
        await self._notify_payout_failure(payout)
        
        return {
            "status": "processed",
            "event": "payout.failed",
            "payout_id": payout['id']
        }
    
    async def _notify_payment_success(self, payment_data: Dict) -> None:
        """Notify about successful payment"""
        # This would typically send a notification to the user
        # and update the user's wallet balance
        pass
    
    async def _notify_payment_failure(self, payment_data: Dict) -> None:
        """Notify about failed payment"""
        # This would typically send a notification to the user
        pass
    
    async def _update_payout_status(self, payout_id: str, status: str) -> None:
        """Update payout status in database"""
        # This would typically update a database record
        pass
    
    async def _notify_payout_failure(self, payout: Dict) -> None:
        """Notify about payout failure"""
        # This would typically send a notification to administrators
        pass
    
    def get_publishable_key(self) -> str:
        """Get Stripe publishable key"""
        return settings.STRIPE_PUBLISHABLE_KEY