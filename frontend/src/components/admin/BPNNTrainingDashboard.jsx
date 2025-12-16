import React, { useState, useEffect } from 'react';
import { 
  LuBrain, 
  LuPlay, 
  LuRefreshCw, 
  LuBarChart3, 
  LuTrash2, 
  LuSettings,
  LuCheckCircle,
  LuXCircle,
  LuClock,
  LuTrendingUp,
  LuActivity
} from 'react-icons/lu';

const API_BASE = "http://localhost/my_little_thingz/backend/api";

const BPNNTrainingDashboard = () => {
  const [modelStatus, setModelStatus] = useState(null);
  const [trainingHistory, setTrainingHistory] = useState([]);
  const [testResults, setTestResults] = useState(null);
  const [statistics, setStatistics] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [trainingConfig, setTrainingConfig] = useState({
    hidden_layers: [8, 6],
    learning_rate: 0.01,
    epochs: 1000,
    validation_split: 0.2,
    training_data_limit: 2000,
    activation_function: 'sigmoid'
  });

  useEffect(() => {
    fetchModelStatus();
    fetchTrainingHistory();
    fetchStatistics();
  }, []);

  const fetchModelStatus = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/bpnn_training.php?action=status`);
      const data = await response.json();
      setModelStatus(data);
    } catch (err) {
      console.error('Failed to fetch model status:', err);
    }
  };

  const fetchTrainingHistory = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/bpnn_training.php?action=history`);
      const data = await response.json();
      setTrainingHistory(data.history || []);
    } catch (err) {
      console.error('Failed to fetch training history:', err);
    }
  };

  const fetchTestResults = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${API_BASE}/admin/bpnn_training.php?action=test`);
      const data = await response.json();
      setTestResults(data);
    } catch (err) {
      setError('Failed to test model');
    } finally {
      setLoading(false);
    }
  };

  const fetchStatistics = async () => {
    try {
      const response = await fetch(`${API_BASE}/admin/bpnn_training.php?action=statistics`);
      const data = await response.json();
      setStatistics(data.statistics);
    } catch (err) {
      console.error('Failed to fetch statistics:', err);
    }
  };

  const trainModel = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`${API_BASE}/admin/bpnn_training.php?action=train`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(trainingConfig)
      });
      
      const data = await response.json();
      
      if (data.status === 'success') {
        alert('Model trained successfully!');
        fetchModelStatus();
        fetchTrainingHistory();
      } else {
        setError(data.message || 'Training failed');
      }
    } catch (err) {
      setError('Network error during training');
    } finally {
      setLoading(false);
    }
  };

  const retrainModel = async () => {
    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`${API_BASE}/admin/bpnn_training.php?action=retrain`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(trainingConfig)
      });
      
      const data = await response.json();
      
      if (data.status === 'success') {
        alert('Model retrained successfully!');
        fetchModelStatus();
        fetchTrainingHistory();
      } else {
        setError(data.message || 'Retraining failed');
      }
    } catch (err) {
      setError('Network error during retraining');
    } finally {
      setLoading(false);
    }
  };

  const cleanupData = async () => {
    if (!confirm('Are you sure you want to cleanup old data? This action cannot be undone.')) {
      return;
    }

    try {
      setLoading(true);
      setError(null);
      
      const response = await fetch(`${API_BASE}/admin/bpnn_training.php?action=cleanup`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          days: 365,
          keep_models: 5
        })
      });
      
      const data = await response.json();
      
      if (data.status === 'success') {
        alert(`Cleanup completed. Deleted ${data.deleted_behaviors} old behavior records.`);
        fetchStatistics();
      } else {
        setError(data.message || 'Cleanup failed');
      }
    } catch (err) {
      setError('Network error during cleanup');
    } finally {
      setLoading(false);
    }
  };

  const formatDate = (dateString) => {
    return new Date(dateString).toLocaleString();
  };

  const formatPercentage = (value) => {
    return `${(value * 100).toFixed(2)}%`;
  };

  const getStatusIcon = (isActive) => {
    return isActive ? <LuCheckCircle className="status-icon active" /> : <LuXCircle className="status-icon inactive" />;
  };

  return (
    <div className="bpnn-dashboard">
      <div className="dashboard-header">
        <h1 className="dashboard-title">
          <LuBrain className="title-icon" />
          BPNN Training Dashboard
        </h1>
        <p className="dashboard-subtitle">Manage and monitor the AI recommendation system</p>
      </div>

      {error && (
        <div className="error-banner">
          <LuXCircle className="error-icon" />
          {error}
        </div>
      )}

      <div className="dashboard-grid">
        {/* Model Status Card */}
        <div className="dashboard-card">
          <div className="card-header">
            <h3 className="card-title">
              <LuActivity className="card-icon" />
              Model Status
            </h3>
          </div>
          <div className="card-content">
            {modelStatus?.has_model ? (
              <div className="model-info">
                <div className="status-row">
                  <span className="status-label">Status:</span>
                  {getStatusIcon(modelStatus.model.is_active)}
                  <span className="status-text">
                    {modelStatus.model.is_active ? 'Active' : 'Inactive'}
                  </span>
                </div>
                <div className="info-row">
                  <span className="info-label">Version:</span>
                  <span className="info-value">{modelStatus.model.version}</span>
                </div>
                <div className="info-row">
                  <span className="info-label">Training Accuracy:</span>
                  <span className="info-value">{formatPercentage(modelStatus.model.training_accuracy)}</span>
                </div>
                <div className="info-row">
                  <span className="info-label">Validation Accuracy:</span>
                  <span className="info-value">{formatPercentage(modelStatus.model.validation_accuracy)}</span>
                </div>
                <div className="info-row">
                  <span className="info-label">Training Data:</span>
                  <span className="info-value">{modelStatus.model.training_data_size} samples</span>
                </div>
                <div className="info-row">
                  <span className="info-label">Last Updated:</span>
                  <span className="info-value">{formatDate(modelStatus.model.created_at)}</span>
                </div>
              </div>
            ) : (
              <div className="no-model">
                <LuXCircle className="no-model-icon" />
                <p>No trained model found</p>
                <p className="no-model-subtitle">Train a model to start AI recommendations</p>
              </div>
            )}
          </div>
        </div>

        {/* Training Configuration Card */}
        <div className="dashboard-card">
          <div className="card-header">
            <h3 className="card-title">
              <LuSettings className="card-icon" />
              Training Configuration
            </h3>
          </div>
          <div className="card-content">
            <div className="config-form">
              <div className="form-group">
                <label className="form-label">Hidden Layers:</label>
                <input
                  type="text"
                  className="form-input"
                  value={trainingConfig.hidden_layers.join(', ')}
                  onChange={(e) => {
                    const layers = e.target.value.split(',').map(l => parseInt(l.trim())).filter(l => !isNaN(l));
                    setTrainingConfig({ ...trainingConfig, hidden_layers: layers });
                  }}
                  placeholder="8, 6"
                />
              </div>
              
              <div className="form-group">
                <label className="form-label">Learning Rate:</label>
                <input
                  type="number"
                  className="form-input"
                  value={trainingConfig.learning_rate}
                  onChange={(e) => setTrainingConfig({ ...trainingConfig, learning_rate: parseFloat(e.target.value) })}
                  step="0.001"
                  min="0.001"
                  max="1"
                />
              </div>
              
              <div className="form-group">
                <label className="form-label">Epochs:</label>
                <input
                  type="number"
                  className="form-input"
                  value={trainingConfig.epochs}
                  onChange={(e) => setTrainingConfig({ ...trainingConfig, epochs: parseInt(e.target.value) })}
                  min="100"
                  max="5000"
                />
              </div>
              
              <div className="form-group">
                <label className="form-label">Training Data Limit:</label>
                <input
                  type="number"
                  className="form-input"
                  value={trainingConfig.training_data_limit}
                  onChange={(e) => setTrainingConfig({ ...trainingConfig, training_data_limit: parseInt(e.target.value) })}
                  min="100"
                  max="10000"
                />
              </div>
              
              <div className="form-group">
                <label className="form-label">Activation Function:</label>
                <select
                  className="form-select"
                  value={trainingConfig.activation_function}
                  onChange={(e) => setTrainingConfig({ ...trainingConfig, activation_function: e.target.value })}
                >
                  <option value="sigmoid">Sigmoid</option>
                  <option value="tanh">Tanh</option>
                  <option value="relu">ReLU</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        {/* Training Actions Card */}
        <div className="dashboard-card">
          <div className="card-header">
            <h3 className="card-title">
              <LuPlay className="card-icon" />
              Training Actions
            </h3>
          </div>
          <div className="card-content">
            <div className="action-buttons">
              <button 
                className="action-button primary"
                onClick={trainModel}
                disabled={loading}
              >
                <LuPlay className="button-icon" />
                {loading ? 'Training...' : 'Train New Model'}
              </button>
              
              <button 
                className="action-button secondary"
                onClick={retrainModel}
                disabled={loading || !modelStatus?.has_model}
              >
                <LuRefreshCw className="button-icon" />
                {loading ? 'Retraining...' : 'Retrain Model'}
              </button>
              
              <button 
                className="action-button tertiary"
                onClick={fetchTestResults}
                disabled={loading || !modelStatus?.has_model}
              >
                <LuBarChart3 className="button-icon" />
                {loading ? 'Testing...' : 'Test Model'}
              </button>
              
              <button 
                className="action-button danger"
                onClick={cleanupData}
                disabled={loading}
              >
                <LuTrash2 className="button-icon" />
                Cleanup Data
              </button>
            </div>
          </div>
        </div>

        {/* Test Results Card */}
        {testResults && (
          <div className="dashboard-card">
            <div className="card-header">
              <h3 className="card-title">
                <LuBarChart3 className="card-icon" />
                Test Results
              </h3>
            </div>
            <div className="card-content">
              <div className="test-results">
                <div className="result-row">
                  <span className="result-label">Accuracy:</span>
                  <span className="result-value">{formatPercentage(testResults.test_results.accuracy)}</span>
                </div>
                <div className="result-row">
                  <span className="result-label">Correct Predictions:</span>
                  <span className="result-value">{testResults.test_results.correct_predictions}</span>
                </div>
                <div className="result-row">
                  <span className="result-label">Total Predictions:</span>
                  <span className="result-value">{testResults.test_results.total_predictions}</span>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Statistics Card */}
        {statistics && (
          <div className="dashboard-card">
            <div className="card-header">
              <h3 className="card-title">
                <LuTrendingUp className="card-icon" />
                System Statistics
              </h3>
            </div>
            <div className="card-content">
              <div className="statistics-grid">
                <div className="stat-item">
                  <span className="stat-label">Total Behaviors:</span>
                  <span className="stat-value">{statistics.total_behaviors?.toLocaleString()}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">Unique Users:</span>
                  <span className="stat-value">{statistics.unique_users?.toLocaleString()}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">Unique Artworks:</span>
                  <span className="stat-value">{statistics.unique_artworks?.toLocaleString()}</span>
                </div>
                <div className="stat-item">
                  <span className="stat-label">Last 24h Activity:</span>
                  <span className="stat-value">{statistics.recent_24h?.toLocaleString()}</span>
                </div>
              </div>
              
              <div className="behavior-breakdown">
                <h4>Behavior Types:</h4>
                {statistics.by_type && Object.entries(statistics.by_type).map(([type, count]) => (
                  <div key={type} className="behavior-item">
                    <span className="behavior-type">{type.replace('_', ' ')}:</span>
                    <span className="behavior-count">{count.toLocaleString()}</span>
                  </div>
                ))}
              </div>
            </div>
          </div>
        )}

        {/* Training History Card */}
        <div className="dashboard-card full-width">
          <div className="card-header">
            <h3 className="card-title">
              <LuClock className="card-icon" />
              Training History
            </h3>
          </div>
          <div className="card-content">
            {trainingHistory.length > 0 ? (
              <div className="history-table">
                <table>
                  <thead>
                    <tr>
                      <th>Version</th>
                      <th>Training Accuracy</th>
                      <th>Validation Accuracy</th>
                      <th>Training Loss</th>
                      <th>Validation Loss</th>
                      <th>Epochs</th>
                      <th>Data Size</th>
                      <th>Status</th>
                      <th>Created</th>
                    </tr>
                  </thead>
                  <tbody>
                    {trainingHistory.map((model, index) => (
                      <tr key={index}>
                        <td>{model.model_version}</td>
                        <td>{formatPercentage(model.training_accuracy)}</td>
                        <td>{formatPercentage(model.validation_accuracy)}</td>
                        <td>{model.training_loss?.toFixed(6)}</td>
                        <td>{model.validation_loss?.toFixed(6)}</td>
                        <td>{model.training_epochs}</td>
                        <td>{model.training_data_size?.toLocaleString()}</td>
                        <td>{getStatusIcon(model.is_active)}</td>
                        <td>{formatDate(model.created_at)}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <div className="no-history">
                <LuClock className="no-history-icon" />
                <p>No training history found</p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default BPNNTrainingDashboard;
















