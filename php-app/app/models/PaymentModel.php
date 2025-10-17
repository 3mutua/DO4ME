<?php
class PaymentModel extends Database {
    protected $table = 'payments';
    
    public function createPayment($paymentData) {
        $sql = "INSERT INTO payments (user_id, amount, currency, payment_method, gateway_reference, status, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        return $this->executeInsert($sql, [
            $paymentData['user_id'],
            $paymentData['amount'],
            $paymentData['currency'] ?? 'USD',
            $paymentData['payment_method'],
            $paymentData['gateway_reference'],
            $paymentData['status'] ?? 'pending',
            json_encode($paymentData['metadata'] ?? [])
        ]);
    }
    
    public function getByGatewayReference($reference) {
        return $this->query("SELECT * FROM payments WHERE gateway_reference = ?", [$reference])->fetch();
    }
    
    public function updatePaymentStatus($paymentId, $status) {
        $sql = "UPDATE payments SET status = ?, completed_at = NOW() WHERE id = ?";
        return $this->query($sql, [$status, $paymentId]);
    }
    
    public function createWithdrawal($withdrawalData) {
        $sql = "INSERT INTO withdrawals (user_id, amount, method, account_details, status) 
                VALUES (?, ?, ?, ?, ?)";
        
        return $this->executeInsert($sql, [
            $withdrawalData['user_id'],
            $withdrawalData['amount'],
            $withdrawalData['method'],
            $withdrawalData['account_details'],
            $withdrawalData['status']
        ]);
    }
    
    public function getPendingWithdrawals($userId) {
        return $this->query("
            SELECT * FROM withdrawals 
            WHERE user_id = ? AND status = 'pending' 
            ORDER BY created_at DESC
        ", [$userId])->fetchAll();
    }
    
    public function getPendingWithdrawalAmount($userId) {
        $result = $this->query("
            SELECT COALESCE(SUM(amount), 0) as total 
            FROM withdrawals 
            WHERE user_id = ? AND status = 'pending'
        ", [$userId])->fetch();
        
        return $result['total'] ?? 0;
    }
}