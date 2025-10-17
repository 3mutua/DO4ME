import requests
import base64
import datetime
from typing import Dict, Any
from ..core.config import settings
import logging

logger = logging.getLogger(__name__)

class MpesaService:
    def __init__(self):
        self.access_token = None
        self.token_expiry = None
    
    async def get_access_token(self) -> str:
        """Get M-Pesa API access token"""
        if self.access_token and self.token_expiry and self.token_expiry > datetime.datetime.utcnow():
            return self.access_token
        
        consumer_key = settings.MPESA_CONSUMER_KEY
        consumer_secret = settings.MPESA_CONSUMER_SECRET
        auth_string = f"{consumer_key}:{consumer_secret}"
        encoded_auth = base64.b64encode(auth_string.encode()).decode()
        
        headers = {
            'Authorization': f'Basic {encoded_auth}'
        }
        
        try:
            response = requests.get(
                'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials',
                headers=headers
            )
            response.raise_for_status()
            
            data = response.json()
            self.access_token = data['access_token']
            # Token expires in 1 hour, set expiry to 55 minutes for safety
            self.token_expiry = datetime.datetime.utcnow() + datetime.timedelta(minutes=55)
            
            return self.access_token
        except Exception as e:
            logger.error(f"Failed to get M-Pesa access token: {str(e)}")
            raise Exception("M-Pesa authentication failed")
    
    async def initiate_stk_push(self, amount: float, phone_number: str, user_id: int) -> Dict[str, Any]:
        """Initiate STK push for M-Pesa payment"""
        try:
            access_token = await self.get_access_token()
            
            timestamp = datetime.datetime.now().strftime('%Y%m%d%H%M%S')
            password = base64.b64encode(
                f"{settings.MPESA_SHORTCODE}{settings.MPESA_PASSKEY}{timestamp}".encode()
            ).decode()
            
            payload = {
                "BusinessShortCode": settings.MPESA_SHORTCODE,
                "Password": password,
                "Timestamp": timestamp,
                "TransactionType": "CustomerPayBillOnline",
                "Amount": amount,
                "PartyA": phone_number,
                "PartyB": settings.MPESA_SHORTCODE,
                "PhoneNumber": phone_number,
                "CallBackURL": f"{settings.APP_URL}/api/v1/mpesa/callback",
                "AccountReference": f"DO4ME{user_id}",
                "TransactionDesc": "Payment for services"
            }
            
            headers = {
                'Authorization': f'Bearer {access_token}',
                'Content-Type': 'application/json'
            }
            
            response = requests.post(
                'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest',
                json=payload,
                headers=headers
            )
            response.raise_for_status()
            
            data = response.json()
            
            if data.get('ResponseCode') == '0':
                return {
                    "payment_intent_id": data['CheckoutRequestID'],
                    "status": "pending",
                    "message": "STK push initiated successfully"
                }
            else:
                raise Exception(f"M-Pesa error: {data.get('ResponseDescription', 'Unknown error')}")
                
        except Exception as e:
            logger.error(f"M-Pesa STK push failed: {str(e)}")
            raise Exception(f"Payment initiation failed: {str(e)}")
    
    async def verify_payment(self, checkout_request_id: str) -> str:
        """Verify M-Pesa payment status"""
        try:
            access_token = await self.get_access_token()
            
            payload = {
                "BusinessShortCode": settings.MPESA_SHORTCODE,
                "Password": base64.b64encode(
                    f"{settings.MPESA_SHORTCODE}{settings.MPESA_PASSKEY}{datetime.datetime.now().strftime('%Y%m%d%H%M%S')}".encode()
                ).decode(),
                "Timestamp": datetime.datetime.now().strftime('%Y%m%d%H%M%S'),
                "CheckoutRequestID": checkout_request_id
            }
            
            headers = {
                'Authorization': f'Bearer {access_token}',
                'Content-Type': 'application/json'
            }
            
            response = requests.post(
                'https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query',
                json=payload,
                headers=headers
            )
            response.raise_for_status()
            
            data = response.json()
            result_code = data.get('ResultCode')
            
            if result_code == '0':
                return 'succeeded'
            elif result_code == '1032':
                return 'cancelled'
            else:
                return 'failed'
                
        except Exception as e:
            logger.error(f"M-Pesa payment verification failed: {str(e)}")
            return 'failed'