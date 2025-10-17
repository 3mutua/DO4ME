import asyncio
import json
import logging
from typing import Dict, Set
from websockets import serve, WebSocketServerProtocol
from websockets.exceptions import ConnectionClosedOK

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class ConnectionManager:
    def __init__(self):
        self.active_connections: Dict[int, Set[WebSocketServerProtocol]] = {}
    
    async def connect(self, user_id: int, websocket: WebSocketServerProtocol):
        if user_id not in self.active_connections:
            self.active_connections[user_id] = set()
        self.active_connections[user_id].add(websocket)
        logger.info(f"User {user_id} connected. Total connections: {len(self.active_connections[user_id])}")
    
    def disconnect(self, user_id: int, websocket: WebSocketServerProtocol):
        if user_id in self.active_connections:
            self.active_connections[user_id].remove(websocket)
            if not self.active_connections[user_id]:
                del self.active_connections[user_id]
        logger.info(f"User {user_id} disconnected. Remaining connections: {len(self.active_connections.get(user_id, []))}")
    
    async def send_personal_message(self, message: str, user_id: int):
        if user_id in self.active_connections:
            for connection in self.active_connections[user_id]:
                try:
                    await connection.send(message)
                except Exception as e:
                    logger.error(f"Error sending message to user {user_id}: {e}")
    
    async def send_task_message(self, message: str, task_id: int, exclude_user_id: int = None):
        # In a real implementation, we would fetch users associated with the task
        # For now, we'll broadcast to all connected users (for demo purposes)
        for user_id, connections in self.active_connections.items():
            if user_id == exclude_user_id:
                continue
            for connection in connections:
                try:
                    await connection.send(message)
                except Exception as e:
                    logger.error(f"Error sending task message to user {user_id}: {e}")

manager = ConnectionManager()

async def handle_websocket(websocket: WebSocketServerProtocol, path: str):
    try:
        # Authenticate user (in a real app, you'd use JWT or similar)
        user_id = int(path.strip('/'))
        await manager.connect(user_id, websocket)
        
        async for message in websocket:
            data = json.loads(message)
            await handle_message(user_id, data, websocket)
    except ValueError:
        await websocket.close(code=1008, reason="Invalid user ID")
    except ConnectionClosedOK:
        pass
    finally:
        manager.disconnect(user_id, websocket)

async def handle_message(user_id: int, data: dict, websocket: WebSocketServerProtocol):
    message_type = data.get('type')
    
    if message_type == 'chat_message':
        await handle_chat_message(user_id, data)
    elif message_type == 'task_update':
        await handle_task_update(user_id, data)
    elif message_type == 'typing_indicator':
        await handle_typing_indicator(user_id, data)

async def handle_chat_message(user_id: int, data: dict):
    message = {
        'type': 'chat_message',
        'from_user_id': user_id,
        'task_id': data['task_id'],
        'message': data['message'],
        'timestamp': data['timestamp']
    }
    
    # Send to all users involved in the task (excluding the sender)
    await manager.send_task_message(
        json.dumps(message),
        data['task_id'],
        exclude_user_id=user_id
    )

async def handle_task_update(user_id: int, data: dict):
    update_message = {
        'type': 'task_update',
        'task_id': data['task_id'],
        'update_type': data['update_type'],
        'message': data['message'],
        'timestamp': data['timestamp']
    }
    
    # Notify all users involved in the task
    await manager.send_task_message(
        json.dumps(update_message),
        data['task_id']
    )

async def handle_typing_indicator(user_id: int, data: dict):
    typing_message = {
        'type': 'typing_indicator',
        'task_id': data['task_id'],
        'user_id': user_id,
        'is_typing': data['is_typing']
    }
    
    # Send to other users in the task
    await manager.send_task_message(
        json.dumps(typing_message),
        data['task_id'],
        exclude_user_id=user_id
    )

async def main():
    server = await serve(handle_websocket, "localhost", 8765)
    logger.info("WebSocket server started on port 8765")
    await server.wait_closed()

if __name__ == "__main__":
    asyncio.run(main())