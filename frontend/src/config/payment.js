// Payment configuration
const PAYMENT_CONFIG = {
  // Set to false to use real Razorpay (with fallback to simulation if keys don't work)
  TEST_MODE: false,
  
  // API endpoints - using hybrid APIs that work with real Razorpay or simulation
  getCreateOrderUrl: (apiBase) => {
    return PAYMENT_CONFIG.TEST_MODE 
      ? `${apiBase}/customer/razorpay-create-order-test.php`
      : `${apiBase}/customer/razorpay-create-order-hybrid.php`;
  },
  
  getVerifyUrl: (apiBase) => {
    return PAYMENT_CONFIG.TEST_MODE 
      ? `${apiBase}/customer/razorpay-verify-test.php`
      : `${apiBase}/customer/razorpay-verify-hybrid.php`;
  },
  
  // Mock Razorpay for test mode
  mockRazorpay: {
    open: function() {
      // Simulate successful payment after 2 seconds
      setTimeout(() => {
        if (this.handler) {
          this.handler({
            razorpay_order_id: this.order_id,
            razorpay_payment_id: 'pay_mock_' + Date.now(),
            razorpay_signature: 'sig_mock_' + Date.now()
          });
        }
      }, 2000);
    }
  }
};

export default PAYMENT_CONFIG;