module.exports = (io) => {
  io.on('connection', (socket) => {
    console.log('User connected:', socket.id);

    // Join user to their own room for private messages
    socket.on('join', (userId) => {
      socket.join(userId);
      console.log(`User ${userId} joined room`);
    });

    // Handle task updates
    socket.on('taskUpdate', (data) => {
      // Notify relevant users about task updates
      socket.to(data.taskId).emit('taskUpdated', data);
    });

    // Handle messaging
    socket.on('sendMessage', (data) => {
      // Emit to the recipient
      socket.to(data.recipientId).emit('newMessage', data);
    });

    // Handle notifications
    socket.on('sendNotification', (data) => {
      socket.to(data.userId).emit('newNotification', data);
    });

    socket.on('disconnect', () => {
      console.log('User disconnected:', socket.id);
    });
  });
};