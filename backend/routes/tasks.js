const express = require('express');
const { body } = require('express-validator');
const {
  createTask,
  getTasks,
  getTask,
  submitProposal
} = require('../controllers/taskController');
const { auth, authorize } = require('../middleware/auth');

const router = express.Router();

// Task validation
const taskValidation = [
  body('title').notEmpty().trim().isLength({ max: 200 }),
  body('description').notEmpty().trim().isLength({ max: 5000 }),
  body('budget').isFloat({ min: 0.01 }),
  body('category').isIn(['writing', 'design', 'programming', 'marketing', 'data_entry', 'customer_service', 'other']),
  body('urgency').isIn(['low', 'medium', 'high']),
  body('durationDays').isInt({ min: 1 })
];

const proposalValidation = [
  body('bidAmount').isFloat({ min: 0.01 }),
  body('coverLetter').notEmpty().trim().isLength({ max: 2000 }),
  body('estimatedDays').isInt({ min: 1 })
];

router.route('/')
  .get(getTasks)
  .post(auth, authorize('client', 'admin'), taskValidation, createTask);

router.route('/:id')
  .get(getTask);

router.post('/:id/proposals', auth, authorize('freelancer'), proposalValidation, submitProposal);

module.exports = router;