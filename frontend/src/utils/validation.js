/**
 * Strict validation utilities for form fields
 * Prevents whitespace-only inputs and provides consistent validation
 */

/**
 * Prevents spacebar input when field is empty or ends with space
 * @param {Event} e - Keyboard event
 * @param {string} currentValue - Current field value
 * @param {boolean} allowSpaces - Whether to allow spaces in the middle of text (default: true)
 */
export const preventSpacebar = (e, currentValue = '', allowSpaces = true) => {
  if (e.key === ' ') {
    // For fields that should never have spaces (like email, password)
    if (!allowSpaces) {
      e.preventDefault();
      return;
    }
    
    // For fields that can have spaces but not at start/end
    if (currentValue === '' || currentValue.endsWith(' ')) {
      e.preventDefault();
      return;
    }
  }
};

/**
 * Trims whitespace from input value
 * @param {string} value - Input value to trim
 * @returns {string} - Trimmed value
 */
export const trimWhitespace = (value) => {
  return typeof value === 'string' ? value.trim() : value;
};

/**
 * Validates that input is not empty or whitespace-only
 * @param {string} value - Input value to validate
 * @param {string} fieldName - Name of the field for error message
 * @returns {string|null} - Error message or null if valid
 */
export const validateRequired = (value, fieldName = 'Field') => {
  if (!value || !trimWhitespace(value)) {
    return `${fieldName} is required`;
  }
  return null;
};

/**
 * Validates email format and prevents spaces
 * @param {string} value - Email value to validate
 * @returns {string|null} - Error message or null if valid
 */
export const validateEmail = (value) => {
  if (!value) return 'Email is required';
  
  const trimmed = trimWhitespace(value);
  if (!trimmed) return 'Email is required';
  
  const emailRegex = /^[\w.!#$%&'*+/=?^_`{|}~-]+@[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)+$/;
  if (!emailRegex.test(trimmed)) {
    return 'Invalid email format';
  }
  
  return null;
};

/**
 * Validates name fields (first name, last name, shop name)
 * @param {string} value - Name value to validate
 * @param {string} fieldName - Name of the field for error message
 * @param {number} minLength - Minimum length (default: 2)
 * @param {number} maxLength - Maximum length (default: 30)
 * @returns {string|null} - Error message or null if valid
 */
export const validateName = (value, fieldName = 'Name', minLength = 2, maxLength = 30) => {
  if (!value) return `${fieldName} is required`;
  
  const trimmed = trimWhitespace(value);
  if (!trimmed) return `${fieldName} is required`;
  
  if (trimmed.length < minLength) {
    return `${fieldName} must be at least ${minLength} characters`;
  }
  
  if (trimmed.length > maxLength) {
    return `${fieldName} must be no more than ${maxLength} characters`;
  }
  
  // Allow letters, spaces, hyphens, and apostrophes
  const nameRegex = new RegExp(`^[A-Za-z][A-Za-z\\s'-]{${minLength - 1},${maxLength - 1}}$`);
  if (!nameRegex.test(trimmed)) {
    return `${fieldName} can only contain letters, spaces, hyphens, and apostrophes`;
  }
  
  return null;
};

/**
 * Validates password strength
 * @param {string} value - Password value to validate
 * @returns {string|null} - Error message or null if valid
 */
export const validatePassword = (value) => {
  if (!value) return 'Password is required';
  
  if (value.length < 8) {
    return 'Password must be at least 8 characters';
  }
  
  if (!/[A-Z]/.test(value)) {
    return 'Password must include an uppercase letter';
  }
  
  if (!/[a-z]/.test(value)) {
    return 'Password must include a lowercase letter';
  }
  
  if (!/[0-9]/.test(value)) {
    return 'Password must include a number';
  }
  
  if (!/[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]/.test(value)) {
    return 'Password must include a special character';
  }
  
  return null;
};

/**
 * Validates that two password fields match
 * @param {string} password - Original password
 * @param {string} confirmPassword - Confirmation password
 * @returns {string|null} - Error message or null if valid
 */
export const validatePasswordMatch = (password, confirmPassword) => {
  if (!confirmPassword) return 'Please confirm your password';
  
  if (password !== confirmPassword) {
    return 'Passwords do not match';
  }
  
  return null;
};

/**
 * Validates shop name for suppliers
 * @param {string} value - Shop name value to validate
 * @param {string|number} role - User role (3 for supplier)
 * @returns {string|null} - Error message or null if valid
 */
export const validateShopName = (value, role) => {
  const isSupplier = String(role) === '3';
  
  if (isSupplier) {
    if (!value) return 'Shop name is required for suppliers';
    
    const trimmed = trimWhitespace(value);
    if (!trimmed) return 'Shop name is required for suppliers';
    
    if (trimmed.length > 120) {
      return 'Shop name must be no more than 120 characters';
    }
  }
  
  return null;
};

/**
 * Enhanced input handler that prevents spaces and trims values
 * @param {Function} setValue - State setter function
 * @param {boolean} allowSpaces - Whether to allow spaces in the field
 * @returns {Function} - Event handler function
 */
export const createInputHandler = (setValue, allowSpaces = true) => {
  return (e) => {
    const { name, value } = e.target;
    const trimmedValue = allowSpaces ? value : value.replace(/\s/g, '');
    setValue(prev => ({ ...prev, [name]: trimmedValue }));
  };
};

/**
 * Enhanced keydown handler that prevents spacebar when appropriate
 * @param {boolean} allowSpaces - Whether to allow spaces in the field
 * @returns {Function} - Keydown event handler function
 */
export const createKeydownHandler = (allowSpaces = true) => {
  return (e) => {
    preventSpacebar(e, e.target.value, allowSpaces);
  };
};

/**
 * Validates all form fields at once
 * @param {Object} formData - Form data object
 * @param {Object} validators - Object containing validator functions
 * @returns {Object} - Object containing validation errors
 */
export const validateForm = (formData, validators) => {
  const errors = {};
  
  Object.keys(validators).forEach(fieldName => {
    const validator = validators[fieldName];
    const value = formData[fieldName];
    const error = validator(value, formData);
    
    if (error) {
      errors[fieldName] = error;
    }
  });
  
  return errors;
};



