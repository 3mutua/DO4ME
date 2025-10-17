<?php
class Validator {
    private $data;
    private $errors = [];
    private $rules = [];
    private $customMessages = [];
    private $fieldNames = [];
    
    public function __construct($data = []) {
        $this->data = $data;
    }
    
    /**
     * Set data to validate
     */
    public function setData($data) {
        $this->data = $data;
        return $this;
    }
    
    /**
     * Set validation rules
     */
    public function setRules($rules) {
        $this->rules = $rules;
        return $this;
    }
    
    /**
     * Set custom error messages
     */
    public function setMessages($messages) {
        $this->customMessages = $messages;
        return $this;
    }
    
    /**
     * Set field names for error messages
     */
    public function setFieldNames($names) {
        $this->fieldNames = $names;
        return $this;
    }
    
    /**
     * Validate data against rules
     */
    public function validate() {
        $this->errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $rules = $this->parseRules($rules);
            $value = $this->getValue($field);
            
            foreach ($rules as $rule) {
                $this->validateRule($field, $value, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    /**
     * Get validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        if (!empty($this->errors)) {
            $firstField = array_key_first($this->errors);
            return $this->errors[$firstField][0] ?? null;
        }
        return null;
    }
    
    /**
     * Check if field has error
     */
    public function hasError($field) {
        return isset($this->errors[$field]);
    }
    
    /**
     * Get error for specific field
     */
    public function getError($field) {
        return $this->errors[$field][0] ?? null;
    }
    
    /**
     * Quick validation static method
     */
    public static function make($data, $rules, $messages = []) {
        $validator = new self($data);
        $validator->setRules($rules);
        $validator->setMessages($messages);
        
        if ($validator->validate()) {
            return $validator->getValidated();
        }
        
        throw new ValidationException('Validation failed', $validator->getErrors());
    }
    
    /**
     * Get validated data (only fields that passed validation)
     */
    public function getValidated() {
        $validated = [];
        
        foreach (array_keys($this->rules) as $field) {
            if (!$this->hasError($field)) {
                $validated[$field] = $this->getValue($field);
            }
        }
        
        return $validated;
    }
    
    /**
     * Parse rules string into array
     */
    private function parseRules($rules) {
        if (is_string($rules)) {
            return explode('|', $rules);
        }
        
        return (array)$rules;
    }
    
    /**
     * Get value from data array with dot notation support
     */
    private function getValue($field) {
        // Handle dot notation for nested arrays
        if (strpos($field, '.') !== false) {
            $keys = explode('.', $field);
            $value = $this->data;
            
            foreach ($keys as $key) {
                if (is_array($value) && array_key_exists($key, $value)) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }
            
            return $value;
        }
        
        return $this->data[$field] ?? null;
    }
    
    /**
     * Validate a single rule
     */
    private function validateRule($field, $value, $rule) {
        $params = [];
        
        // Check if rule has parameters
        if (strpos($rule, ':') !== false) {
            list($rule, $paramStr) = explode(':', $rule, 2);
            $params = explode(',', $paramStr);
        }
        
        $method = 'validate' . ucfirst($rule);
        
        if (method_exists($this, $method)) {
            if (!$this->$method($value, ...$params)) {
                $this->addError($field, $rule, $params);
            }
        } else {
            throw new Exception("Validation rule '$rule' does not exist");
        }
    }
    
    /**
     * Add error message
     */
    private function addError($field, $rule, $params = []) {
        $message = $this->getErrorMessage($field, $rule, $params);
        
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        
        $this->errors[$field][] = $message;
    }
    
    /**
     * Get error message for rule
     */
    private function getErrorMessage($field, $rule, $params) {
        $fieldName = $this->getFieldName($field);
        
        // Check for custom message
        $customKey = "{$field}.{$rule}";
        if (isset($this->customMessages[$customKey])) {
            return $this->formatMessage($this->customMessages[$customKey], $fieldName, $params);
        }
        
        if (isset($this->customMessages[$rule])) {
            return $this->formatMessage($this->customMessages[$rule], $fieldName, $params);
        }
        
        // Default messages
        $messages = [
            'required' => 'The {field} field is required.',
            'email' => 'The {field} must be a valid email address.',
            'min' => 'The {field} must be at least {param1}.',
            'max' => 'The {field} may not be greater than {param1}.',
            'between' => 'The {field} must be between {param1} and {param2}.',
            'numeric' => 'The {field} must be a number.',
            'integer' => 'The {field} must be an integer.',
            'string' => 'The {field} must be a string.',
            'array' => 'The {field} must be an array.',
            'boolean' => 'The {field} field must be true or false.',
            'confirmed' => 'The {field} confirmation does not match.',
            'different' => 'The {field} and {param1} must be different.',
            'same' => 'The {field} and {param1} must match.',
            'in' => 'The selected {field} is invalid.',
            'not_in' => 'The selected {field} is invalid.',
            'regex' => 'The {field} format is invalid.',
            'date' => 'The {field} is not a valid date.',
            'date_format' => 'The {field} does not match the format {param1}.',
            'before' => 'The {field} must be a date before {param1}.',
            'after' => 'The {field} must be a date after {param1}.',
            'alpha' => 'The {field} may only contain letters.',
            'alpha_num' => 'The {field} may only contain letters and numbers.',
            'alpha_dash' => 'The {field} may only contain letters, numbers, dashes and underscores.',
            'url' => 'The {field} format is invalid.',
            'ip' => 'The {field} must be a valid IP address.',
            'phone' => 'The {field} must be a valid phone number.',
            'credit_card' => 'The {field} must be a valid credit card number.',
            'json' => 'The {field} must be a valid JSON string.',
            'file' => 'The {field} must be a file.',
            'image' => 'The {field} must be an image.',
            'mimes' => 'The {field} must be a file of type: {param1}.',
            'size' => 'The {field} must be {param1} kilobytes.',
            'unique' => 'The {field} has already been taken.',
            'exists' => 'The selected {field} is invalid.',
        ];
        
        $message = $messages[$rule] ?? 'The {field} field is invalid.';
        return $this->formatMessage($message, $fieldName, $params);
    }
    
    /**
     * Format error message with parameters
     */
    private function formatMessage($message, $fieldName, $params) {
        $replacements = ['{field}' => $fieldName];
        
        foreach ($params as $index => $param) {
            $replacements["{param" . ($index + 1) . "}"] = $param;
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
    
    /**
     * Get field name for error messages
     */
    private function getFieldName($field) {
        if (isset($this->fieldNames[$field])) {
            return $this->fieldNames[$field];
        }
        
        // Convert snake_case to Title Case
        return ucwords(str_replace(['_', '.'], ' ', $field));
    }
    
    // ==================== VALIDATION RULES ====================
    
    /**
     * Required rule
     */
    private function validateRequired($value) {
        if (is_null($value)) {
            return false;
        } elseif (is_string($value)) {
            return trim($value) !== '';
        } elseif (is_array($value)) {
            return count($value) > 0;
        }
        
        return true;
    }
    
    /**
     * Email rule
     */
    private function validateEmail($value) {
        if (is_null($value) || $value === '') {
            return true; // Use required rule for empty values
        }
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Minimum length/value rule
     */
    private function validateMin($value, $min) {
        if (is_null($value)) {
            return true;
        }
        
        if (is_numeric($value)) {
            return $value >= $min;
        } elseif (is_string($value)) {
            return mb_strlen($value) >= $min;
        } elseif (is_array($value)) {
            return count($value) >= $min;
        }
        
        return false;
    }
    
    /**
     * Maximum length/value rule
     */
    private function validateMax($value, $max) {
        if (is_null($value)) {
            return true;
        }
        
        if (is_numeric($value)) {
            return $value <= $max;
        } elseif (is_string($value)) {
            return mb_strlen($value) <= $max;
        } elseif (is_array($value)) {
            return count($value) <= $max;
        }
        
        return false;
    }
    
    /**
     * Between rule
     */
    private function validateBetween($value, $min, $max) {
        return $this->validateMin($value, $min) && $this->validateMax($value, $max);
    }
    
    /**
     * Numeric rule
     */
    private function validateNumeric($value) {
        if (is_null($value)) {
            return true;
        }
        return is_numeric($value);
    }
    
    /**
     * Integer rule
     */
    private function validateInteger($value) {
        if (is_null($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }
    
    /**
     * String rule
     */
    private function validateString($value) {
        if (is_null($value)) {
            return true;
        }
        return is_string($value);
    }
    
    /**
     * Array rule
     */
    private function validateArray($value) {
        if (is_null($value)) {
            return true;
        }
        return is_array($value);
    }
    
    /**
     * Boolean rule
     */
    private function validateBoolean($value) {
        if (is_null($value)) {
            return true;
        }
        return in_array($value, [true, false, 0, 1, '0', '1', 'true', 'false'], true);
    }
    
    /**
     * Confirmed rule (check for field_confirmation)
     */
    private function validateConfirmed($value, $field) {
        $confirmationValue = $this->getValue($field . '_confirmation');
        return $value === $confirmationValue;
    }
    
    /**
     * Different from another field
     */
    private function validateDifferent($value, $otherField) {
        $otherValue = $this->getValue($otherField);
        return $value !== $otherValue;
    }
    
    /**
     * Same as another field
     */
    private function validateSame($value, $otherField) {
        $otherValue = $this->getValue($otherField);
        return $value === $otherValue;
    }
    
    /**
     * In array rule
     */
    private function validateIn($value, ...$allowed) {
        if (is_null($value)) {
            return true;
        }
        return in_array($value, $allowed);
    }
    
    /**
     * Not in array rule
     */
    private function validateNot_in($value, ...$disallowed) {
        if (is_null($value)) {
            return true;
        }
        return !in_array($value, $disallowed);
    }
    
    /**
     * Regular expression rule
     */
    private function validateRegex($value, $pattern) {
        if (is_null($value)) {
            return true;
        }
        return preg_match($pattern, $value) === 1;
    }
    
    /**
     * Date rule
     */
    private function validateDate($value) {
        if (is_null($value)) {
            return true;
        }
        return strtotime($value) !== false;
    }
    
    /**
     * Date format rule
     */
    private function validateDate_format($value, $format) {
        if (is_null($value)) {
            return true;
        }
        
        $date = DateTime::createFromFormat($format, $value);
        return $date && $date->format($format) === $value;
    }
    
    /**
     * Before date rule
     */
    private function validateBefore($value, $date) {
        if (is_null($value)) {
            return true;
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($date);
        
        return $valueTime !== false && $compareTime !== false && $valueTime < $compareTime;
    }
    
    /**
     * After date rule
     */
    private function validateAfter($value, $date) {
        if (is_null($value)) {
            return true;
        }
        
        $valueTime = strtotime($value);
        $compareTime = strtotime($date);
        
        return $valueTime !== false && $compareTime !== false && $valueTime > $compareTime;
    }
    
    /**
     * Alpha characters only
     */
    private function validateAlpha($value) {
        if (is_null($value)) {
            return true;
        }
        return preg_match('/^[a-zA-Z]+$/', $value) === 1;
    }
    
    /**
     * Alpha-numeric characters
     */
    private function validateAlpha_num($value) {
        if (is_null($value)) {
            return true;
        }
        return preg_match('/^[a-zA-Z0-9]+$/', $value) === 1;
    }
    
    /**
     * Alpha-numeric with dashes and underscores
     */
    private function validateAlpha_dash($value) {
        if (is_null($value)) {
            return true;
        }
        return preg_match('/^[a-zA-Z0-9_-]+$/', $value) === 1;
    }
    
    /**
     * URL validation
     */
    private function validateUrl($value) {
        if (is_null($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * IP address validation
     */
    private function validateIp($value) {
        if (is_null($value)) {
            return true;
        }
        return filter_var($value, FILTER_VALIDATE_IP) !== false;
    }
    
    /**
     * Phone number validation (basic)
     */
    private function validatePhone($value) {
        if (is_null($value)) {
            return true;
        }
        return preg_match('/^[\+]?[0-9\s\-\(\)]{10,20}$/', $value) === 1;
    }
    
    /**
     * Credit card validation (basic Luhn check)
     */
    private function validateCredit_card($value) {
        if (is_null($value)) {
            return true;
        }
        
        $value = preg_replace('/\D/', '', $value);
        
        // Luhn algorithm
        $length = strlen($value);
        $sum = 0;
        $parity = $length % 2;
        
        for ($i = 0; $i < $length; $i++) {
            $digit = $value[$i];
            if ($i % 2 == $parity) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        
        return $sum % 10 == 0;
    }
    
    /**
     * JSON validation
     */
    private function validateJson($value) {
        if (is_null($value)) {
            return true;
        }
        
        if (!is_string($value)) {
            return false;
        }
        
        json_decode($value);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    /**
     * File validation
     */
    private function validateFile($value) {
        if (is_null($value)) {
            return true;
        }
        return is_array($value) && isset($value['tmp_name']) && is_uploaded_file($value['tmp_name']);
    }
    
    /**
     * Image file validation
     */
    private function validateImage($value) {
        if (is_null($value) || !$this->validateFile($value)) {
            return true;
        }
        
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);
        
        return in_array($mimeType, $allowedTypes);
    }
    
    /**
     * File MIME types validation
     */
    private function validateMimes($value, ...$allowedTypes) {
        if (is_null($value) || !$this->validateFile($value)) {
            return true;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $value['tmp_name']);
        finfo_close($finfo);
        
        return in_array($mimeType, $allowedTypes);
    }
    
    /**
     * File size validation (in KB)
     */
    private function validateSize($value, $sizeKB) {
        if (is_null($value) || !$this->validateFile($value)) {
            return true;
        }
        
        $sizeBytes = $sizeKB * 1024;
        return $value['size'] <= $sizeBytes;
    }
    
    /**
     * Unique value in database (basic implementation)
     */
    private function validateUnique($value, $table, $column = null) {
        if (is_null($value)) {
            return true;
        }
        
        if ($column === null) {
            $column = $this->getFieldFromRule();
        }
        
        // This would typically query the database
        // For now, we'll return true (assuming unique)
        return true;
    }
    
    /**
     * Value exists in database (basic implementation)
     */
    private function validateExists($value, $table, $column = null) {
        if (is_null($value)) {
            return true;
        }
        
        if ($column === null) {
            $column = $this->getFieldFromRule();
        }
        
        // This would typically query the database
        // For now, we'll return true (assuming exists)
        return true;
    }
    
    /**
     * Optional rule (always passes if value is null/empty)
     */
    private function validateOptional($value) {
        return true; // Always passes, used to skip validation if field is empty
    }
    
    /**
     * Helper to get field name for database rules
     */
    private function getFieldFromRule() {
        // This would be implemented to get the current field being validated
        return 'field';
    }
    
    /**
     * Sanitize data
     */
    public function sanitize($data = null) {
        if ($data === null) {
            $data = $this->data;
        }
        
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->sanitize($value);
            }
            return $data;
        }
        
        if (is_string($data)) {
            // Basic sanitization
            $data = trim($data);
            $data = stripslashes($data);
            $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        }
        
        return $data;
    }
    
    /**
     * Get only specific fields from data
     */
    public function only($fields) {
        $fields = is_array($fields) ? $fields : func_get_args();
        $result = [];
        
        foreach ($fields as $field) {
            if (array_key_exists($field, $this->data)) {
                $result[$field] = $this->data[$field];
            }
        }
        
        return $result;
    }
    
    /**
     * Get all data except specific fields
     */
    public function except($fields) {
        $fields = is_array($fields) ? $fields : func_get_args();
        $result = $this->data;
        
        foreach ($fields as $field) {
            unset($result[$field]);
        }
        
        return $result;
    }
}

/**
 * Custom Validation Exception
 */
class ValidationException extends Exception {
    private $validationErrors;
    
    public function __construct($message = "", $errors = [], $code = 0, Throwable $previous = null) {
        $this->validationErrors = $errors;
        parent::__construct($message, $code, $previous);
    }
    
    public function getErrors() {
        return $this->validationErrors;
    }
    
    public function getFirstError() {
        if (!empty($this->validationErrors)) {
            $firstField = array_key_first($this->validationErrors);
            return $this->validationErrors[$firstField][0] ?? null;
        }
        return null;
    }
}