const express = require('express');
const { processPayment, getPayments, getPayment, handleWebhook } = require('../controllers/paymentController');
const { auth } = require('../middleware/auth');

const router = express.Router();

router.route('/')
  .get(auth, getPayments)
  .post(auth, processPayment);

router.route('/:id')
  .get(auth, getPayment);

router.post('/webhook/:gateway', handleWebhook);

module.exports = router;