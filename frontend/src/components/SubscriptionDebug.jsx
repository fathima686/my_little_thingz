import React, { useState } from 'react';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function SubscriptionDebug() {
  const { tutorialAuth } = useTutorialAuth();
  const [testEmail, setTestEmail] = useState(tutorialAuth?.email || 'test@example.com');
  const [testResult, setTestResult] = useState(null);
  const [loading, setLoading] = useState(false);

  const testDatabaseSetup = async () => {
    setLoading(true);
    setTestResult(null);
    
    try {
      console.log('Testing database setup...');
      
      const res = await fetch(`http://localhost/my_little_thingz/backend/test-subscription-flow.php`, {
        method: 'GET'
      });

      const responseText = await res.text();
      console.log('Database setup response:', responseText);

      setTestResult({
        type: 'database_setup',
        status: res.status,
        ok: res.ok,
        response: responseText
      });
    } catch (error) {
      console.error('Database setup error:', error);
      setTestResult({
        type: 'database_setup',
        error: error.message
      });
    } finally {
      setLoading(false);
    }
  };

  const testSubscriptionAPI = async () => {
    setLoading(true);
    setTestResult(null);
    
    try {
      console.log('Testing subscription API with:', { testEmail, tutorialAuth });
      
      const res = await fetch(`${API_BASE}/customer/create-subscription.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Tutorial-Email': testEmail
        },
        body: JSON.stringify({
          plan_code: 'pro',
          billing_period: 'monthly'
        })
      });

      console.log('Response status:', res.status);
      console.log('Response headers:', [...res.headers.entries()]);

      const responseText = await res.text();
      console.log('Raw response:', responseText);

      let data;
      try {
        data = JSON.parse(responseText);
      } catch (e) {
        data = { error: 'Invalid JSON', raw: responseText };
      }

      setTestResult({
        type: 'subscription_api',
        status: res.status,
        ok: res.ok,
        data: data
      });
    } catch (error) {
      console.error('Test error:', error);
      setTestResult({
        type: 'subscription_api',
        error: error.message,
        stack: error.stack
      });
    } finally {
      setLoading(false);
    }
  };

  const resetSubscriptions = async () => {
    setLoading(true);
    setTestResult(null);
    
    try {
      console.log('Resetting subscriptions for:', testEmail);
      
      const res = await fetch(`http://localhost/my_little_thingz/backend/reset-subscriptions.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email: testEmail
        })
      });

      const data = await res.json();
      console.log('Reset response:', data);

      setTestResult({
        type: 'reset_subscriptions',
        status: res.status,
        ok: res.ok,
        data: data
      });
    } catch (error) {
      console.error('Reset error:', error);
      setTestResult({
        type: 'reset_subscriptions',
        error: error.message
      });
    } finally {
      setLoading(false);
    }
  };

  const testRazorpaySDK = async () => {
    setLoading(true);
    setTestResult(null);
    
    try {
      console.log('Testing Razorpay SDK...');
      
      const res = await fetch(`http://localhost/my_little_thingz/backend/test-razorpay-sdk.php`, {
        method: 'GET'
      });

      const responseText = await res.text();
      console.log('Razorpay SDK test response:', responseText);

      setTestResult({
        type: 'razorpay_sdk_test',
        status: res.status,
        ok: res.ok,
        response: responseText
      });
    } catch (error) {
      console.error('Razorpay SDK test error:', error);
      setTestResult({
        type: 'razorpay_sdk_test',
        error: error.message
      });
    } finally {
      setLoading(false);
    }
  };

  const testFullFlow = async () => {
    setLoading(true);
    setTestResult(null);
    
    try {
      console.log('Testing full subscription flow...');
      
      const res = await fetch(`http://localhost/my_little_thingz/backend/test-subscription-flow.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email: testEmail,
          plan_code: 'pro'
        })
      });

      const responseText = await res.text();
      console.log('Full flow response:', responseText);

      setTestResult({
        type: 'full_flow',
        status: res.status,
        ok: res.ok,
        response: responseText
      });
    } catch (error) {
      console.error('Full flow error:', error);
      setTestResult({
        type: 'full_flow',
        error: error.message
      });
    } finally {
      setLoading(false);
    }
  };

  return (
    <div style={{ padding: '20px', border: '2px solid #007bff', margin: '20px', borderRadius: '8px', backgroundColor: '#f8f9fa' }}>
      <h3 style={{ color: '#007bff' }}>🔧 Subscription Debug Panel</h3>
      
      <div style={{ marginBottom: '15px', padding: '10px', backgroundColor: '#e9ecef', borderRadius: '4px' }}>
        <strong>Tutorial Auth:</strong> 
        <pre style={{ fontSize: '12px', margin: '5px 0' }}>
          {JSON.stringify(tutorialAuth, null, 2)}
        </pre>
      </div>
      
      <div style={{ marginBottom: '15px' }}>
        <label style={{ display: 'block', marginBottom: '5px', fontWeight: 'bold' }}>Test Email: </label>
        <input 
          type="email" 
          value={testEmail} 
          onChange={(e) => setTestEmail(e.target.value)}
          style={{ width: '100%', padding: '8px', borderRadius: '4px', border: '1px solid #ccc' }}
        />
      </div>
      
      <div style={{ display: 'flex', gap: '10px', marginBottom: '20px', flexWrap: 'wrap' }}>
        <button 
          onClick={resetSubscriptions} 
          disabled={loading}
          style={{ padding: '10px 15px', backgroundColor: '#dc3545', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
        >
          {loading ? 'Resetting...' : '0. Reset Subscriptions'}
        </button>
        
        <button 
          onClick={testDatabaseSetup} 
          disabled={loading}
          style={{ padding: '10px 15px', backgroundColor: '#28a745', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
        >
          {loading ? 'Testing...' : '1. Setup Database'}
        </button>
        
        <button 
          onClick={testRazorpaySDK} 
          disabled={loading}
          style={{ padding: '10px 15px', backgroundColor: '#fd7e14', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
        >
          {loading ? 'Testing...' : '2. Test Razorpay SDK'}
        </button>
        
        <button 
          onClick={testSubscriptionAPI} 
          disabled={loading}
          style={{ padding: '10px 15px', backgroundColor: '#007bff', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
        >
          {loading ? 'Testing...' : '3. Test Subscription API'}
        </button>
        
        <button 
          onClick={testFullFlow} 
          disabled={loading}
          style={{ padding: '10px 15px', backgroundColor: '#17a2b8', color: 'white', border: 'none', borderRadius: '4px', cursor: 'pointer' }}
        >
          {loading ? 'Testing...' : '4. Test Full Flow'}
        </button>
      </div>
      
      {testResult && (
        <div style={{ marginTop: '20px', padding: '15px', backgroundColor: testResult.error ? '#f8d7da' : '#d4edda', borderRadius: '4px', border: `1px solid ${testResult.error ? '#f5c6cb' : '#c3e6cb'}` }}>
          <h4 style={{ margin: '0 0 10px 0', color: testResult.error ? '#721c24' : '#155724' }}>
            Test Result ({testResult.type}):
          </h4>
          <pre style={{ whiteSpace: 'pre-wrap', fontSize: '11px', maxHeight: '400px', overflow: 'auto', margin: 0 }}>
            {testResult.response || JSON.stringify(testResult, null, 2)}
          </pre>
        </div>
      )}
      
      <div style={{ marginTop: '15px', padding: '10px', backgroundColor: '#fff3cd', borderRadius: '4px', border: '1px solid #ffeaa7' }}>
        <strong>🚨 NEW ISSUE FOUND:</strong> Razorpay SDK not loading properly!
        <br/><br/>
        <strong>Updated Instructions:</strong>
        <ol style={{ margin: '5px 0', paddingLeft: '20px' }}>
          <li><strong>First click "0. Reset Subscriptions"</strong> to clear existing test data</li>
          <li>Then click "1. Setup Database" to ensure tables exist</li>
          <li><strong>Then click "2. Test Razorpay SDK"</strong> to verify SDK is working</li>
          <li>Then click "3. Test Subscription API" to test the real API</li>
          <li>Finally try the actual subscription flow - should now show Razorpay!</li>
        </ol>
      </div>
    </div>
  );
}