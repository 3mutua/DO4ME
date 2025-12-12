const express = require('express');
const { getNotifications, markAsRead, deleteNotification } = require('../controllers/notificationController');
const { auth } = require('../middleware/auth');

const router = express.Router();

router.route('/')
  .get(auth, getNotifications);

router.route('/:id/read')
  .put(auth, markAsRead);

router.route('/:id')
  .delete(auth, deleteNotification);

module.exports = router;