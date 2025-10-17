<?php
class WalletModel extends Database {
    
    public function getBalance($userId) {
        $result = $this->query("
            SELECT wallet_balance FROM users WHERE id = ?
        ", [$userId])->fetch();
        
        return $result['wallet_balance'] ?? 0;
    }
    
    public function addFunds($userId, $amount, $type, $description) {
        $this->beginTransaction();
        
        try {
            // Update user balance
            $this->query("
                UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?
            ", [$amount, $userId]);
            
            // Record transaction
            $newBalance = $this->getBalance($userId);
            $this->query("
                INSERT INTO wallet_transactions (user_id, amount, type, description, balance_after) 
                VALUES (?, ?, ?, ?, ?)
            ", [$userId, $amount, $type, $description, $newBalance]);
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    
    public function deductFunds($userId, $amount, $type, $description) {
        $currentBalance = $this->getBalance($userId);
        
        if ($currentBalance < $amount) {
            throw new Exception("Insufficient funds");
        }
        
        $this->beginTransaction();
        
        try {
            // Update user balance
            $this->query("
                UPDATE users SET wallet_balance = wallet_balance - ? WHERE id = ?
            ", [$amount, $userId]);
            
            // Record transaction (negative amount for deduction)
            $newBalance = $currentBalance - $amount;
            $this->query("
                INSERT INTO wallet_transactions (user_id, amount, type, description, balance_after) 
                VALUES (?, ?, ?, ?, ?)
            ", [$userId, -$amount, $type, $description, $newBalance]);
            
            $this->commit();
            return true;
            
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        }
    }
    
    public function getTransactions($userId, $limit = 20) {
        return $this->query("
            SELECT * FROM wallet_transactions 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT ?
        ", [$userId, $limit])->fetchAll();
    }
}