const express = require('express');
const { getUsers, getUser, updateUser, deleteUser } = require('../controllers/userController');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

router.route('/')
  .get(auth, authorize('admin'), getUsers);

router.route('/:id')
  .get(auth, getUser)
  .put(auth, updateUser)
  .delete(auth, authorize('admin'), deleteUser);

module.exports = router;