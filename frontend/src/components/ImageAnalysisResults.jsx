import React, { useState } from 'react';
import { 
  LuShield, LuCheck, LuX, LuCamera, LuImage, LuCopy, LuClock, LuInfo,
  LuChevronDown, LuChevronUp, LuZap, LuEye, LuUpload
} from 'react-icons/lu';
import '../styles/image-analysis-results.css';

const ImageAnalysisResults = ({ analysisResults, onClose }) => {
  const [expandedSections, setExpandedSections] = useState({
    authenticity: true,
    metadata: false,
    editing: false,
    similarity: false
  });

  const toggleSection = (section) => {
    setExpandedSections(prev => ({
      ...prev,
      [section]: !prev[section]
    }));
  };

  const getRiskIcon = (riskLevel) => {
    switch (riskLevel) {
      case 'clean': return <LuCheck className="risk-icon clean" />;
      case 'suspicious': return <LuInfo className="risk-icon suspicious" />;
      case 'highly_suspicious': return <LuX className="risk-icon highly-suspicious" />;
      default: return <LuShield className="risk-icon unknown" />;
    }
  };

  const getRiskColor = (riskLevel) => {
    switch (riskLevel) {
      case 'clean': return '#10b981';
      case 'suspicious': return '#f59e0b';
      case 'highly_suspicious': return '#ef4444';
      default: return '#6b7280';
    }
  };

  const getScoreColor = (score) => {
    if (score <= 30) return '#10b981';
    if (score <= 60) return '#f59e0b';
    return '#ef4444';
  };

  const formatCameraInfo = (cameraInfo) => {
    if (!cameraInfo || typeof cameraInfo === 'string') {
      try {
        cameraInfo = JSON.parse(cameraInfo || '{}');
      } catch {
        return {};
      }
    }
    return cameraInfo;
  };

  const formatEditingInfo = (editingInfo) => {
    if (!editingInfo || typeof editingInfo === 'string') {
      try {
        editingInfo = JSON.parse(editingInfo || '{}');
      } catch {
        return {};
      }
    }
    return editingInfo;
  };

  const formatSimilarityMatches = (matches) => {
    if (!matches || typeof matches === 'string') {
      try {
        matches = JSON.parse(matches || '[]');
      } catch {
        return [];
      }
    }
    return Array.isArray(matches) ? matches : [];
  };

  return (
    <div className="image-analysis-overlay">
      <div className="image-analysis-modal">
        <div className="analysis-header">
          <div className="header-content">
            <LuZap className="analysis-icon" />
            <div>
              <h2>AI Image Analysis Results</h2>
              <p>Comprehensive authenticity and quality assessment</p>
            </div>
          </div>
          <button className="close-btn" onClick={onClose}>×</button>
        </div>

        <div className="analysis-content">
          {analysisResults.map((result, index) => (
            <div key={index} className="image-result-card">
              <div className="image-header">
                <div className="image-info">
                  <LuImage className="image-icon" />
                  <span className="image-name">{result.file_name}</span>
                </div>
                <div className="overall-status">
                  {getRiskIcon(result.risk_level)}
                  <span className={`status-text ${result.risk_level}`}>
                    {result.risk_level?.replace('_', ' ').toUpperCase() || 'PROCESSING'}
                  </span>
                </div>
              </div>

              {/* Authenticity Score Section */}
              <div className="analysis-section">
                <div 
                  className="section-header"
                  onClick={() => toggleSection('authenticity')}
                >
                  <div className="section-title">
                    <LuShield className="section-icon" />
                    <span>Authenticity Assessment</span>
                  </div>
                  {expandedSections.authenticity ? <LuChevronUp /> : <LuChevronDown />}
                </div>
                
                {expandedSections.authenticity && (
                  <div className="section-content">
                    <div className="score-display">
                      <div className="score-circle">
                        <svg viewBox="0 0 100 100" className="score-svg">
                          <circle
                            cx="50"
                            cy="50"
                            r="45"
                            fill="none"
                            stroke="#e5e7eb"
                            strokeWidth="8"
                          />
                          <circle
                            cx="50"
                            cy="50"
                            r="45"
                            fill="none"
                            stroke={getScoreColor(result.authenticity_score || 0)}
                            strokeWidth="8"
                            strokeDasharray={`${(result.authenticity_score || 0) * 2.83} 283`}
                            strokeLinecap="round"
                            transform="rotate(-90 50 50)"
                          />
                        </svg>
                        <div className="score-text">
                          <span className="score-number">{result.authenticity_score || 0}</span>
                          <span className="score-label">Score</span>
                        </div>
                      </div>
                      
                      <div className="score-details">
                        <div className="score-interpretation">
                          <h4>Assessment Summary</h4>
                          <p className={`interpretation ${result.risk_level}`}>
                            {result.authenticity_score <= 30 && "Excellent authenticity indicators. Image appears genuine with natural characteristics."}
                            {result.authenticity_score > 30 && result.authenticity_score <= 60 && "Good authenticity with minor concerns. Some editing detected but within normal range."}
                            {result.authenticity_score > 60 && "Significant authenticity concerns detected. Multiple editing indicators found."}
                          </p>
                        </div>
                        
                        {result.flagged_reasons && result.flagged_reasons.length > 0 && (
                          <div className="flagged-reasons">
                            <h4>Detected Issues</h4>
                            <ul>
                              {result.flagged_reasons.map((reason, idx) => (
                                <li key={idx} className="flagged-reason">
                                  <LuInfo className="reason-icon" />
                                  {reason}
                                </li>
                              ))}
                            </ul>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                )}
              </div>

              {/* Camera & Metadata Section */}
              <div className="analysis-section">
                <div 
                  className="section-header"
                  onClick={() => toggleSection('metadata')}
                >
                  <div className="section-title">
                    <LuCamera className="section-icon" />
                    <span>Camera & Metadata Analysis</span>
                  </div>
                  {expandedSections.metadata ? <LuChevronUp /> : <LuChevronDown />}
                </div>
                
                {expandedSections.metadata && (
                  <div className="section-content">
                    <div className="metadata-grid">
                      {(() => {
                        const cameraInfo = formatCameraInfo(result.camera_info);
                        return (
                          <>
                            {cameraInfo.make && (
                              <div className="metadata-item">
                                <span className="metadata-label">Camera Make</span>
                                <span className="metadata-value">{cameraInfo.make}</span>
                              </div>
                            )}
                            {cameraInfo.model && (
                              <div className="metadata-item">
                                <span className="metadata-label">Camera Model</span>
                                <span className="metadata-value">{cameraInfo.model}</span>
                              </div>
                            )}
                            {cameraInfo.datetime_original && (
                              <div className="metadata-item">
                                <span className="metadata-label">Date Taken</span>
                                <span className="metadata-value">{cameraInfo.datetime_original}</span>
                              </div>
                            )}
                            {cameraInfo.has_gps !== undefined && (
                              <div className="metadata-item">
                                <span className="metadata-label">GPS Data</span>
                                <span className={`metadata-value ${cameraInfo.has_gps ? 'present' : 'absent'}`}>
                                  {cameraInfo.has_gps ? 'Present' : 'Not Available'}
                                </span>
                              </div>
                            )}
                            {result.file_size && (
                              <div className="metadata-item">
                                <span className="metadata-label">File Size</span>
                                <span className="metadata-value">
                                  {(result.file_size / 1024 / 1024).toFixed(2)} MB
                                </span>
                              </div>
                            )}
                            {result.image_dimensions && (
                              <div className="metadata-item">
                                <span className="metadata-label">Dimensions</span>
                                <span className="metadata-value">{result.image_dimensions}</span>
                              </div>
                            )}
                          </>
                        );
                      })()}
                    </div>
                  </div>
                )}
              </div>

              {/* Editing Software Detection */}
              <div className="analysis-section">
                <div 
                  className="section-header"
                  onClick={() => toggleSection('editing')}
                >
                  <div className="section-title">
                    <LuUpload className="section-icon" />
                    <span>Editing Software Detection</span>
                  </div>
                  {expandedSections.editing ? <LuChevronUp /> : <LuChevronDown />}
                </div>
                
                {expandedSections.editing && (
                  <div className="section-content">
                    {(() => {
                      const editingInfo = formatEditingInfo(result.editing_software);
                      const detectedSoftware = editingInfo.detected_software || [];
                      
                      return (
                        <div className="editing-analysis">
                          {detectedSoftware.length > 0 ? (
                            <div className="detected-software">
                              <h4>Detected Software</h4>
                              {detectedSoftware.map((software, idx) => (
                                <div key={idx} className="software-item">
                                  <div className="software-name">{software.name}</div>
                                  <div className={`confidence-badge ${software.confidence}`}>
                                    {software.confidence} confidence
                                  </div>
                                </div>
                              ))}
                            </div>
                          ) : (
                            <div className="no-editing">
                              <LuCheck className="clean-icon" />
                              <span>No editing software signatures detected</span>
                            </div>
                          )}
                          
                          {editingInfo.editing_indicators && editingInfo.editing_indicators.length > 0 && (
                            <div className="editing-indicators">
                              <h4>Editing Indicators</h4>
                              <ul>
                                {editingInfo.editing_indicators.map((indicator, idx) => (
                                  <li key={idx}>{indicator.replace('_', ' ')}</li>
                                ))}
                              </ul>
                            </div>
                          )}
                        </div>
                      );
                    })()}
                  </div>
                )}
              </div>

              {/* Similarity Analysis */}
              <div className="analysis-section">
                <div 
                  className="section-header"
                  onClick={() => toggleSection('similarity')}
                >
                  <div className="section-title">
                    <LuCopy className="section-icon" />
                    <span>Similarity Analysis</span>
                  </div>
                  {expandedSections.similarity ? <LuChevronUp /> : <LuChevronDown />}
                </div>
                
                {expandedSections.similarity && (
                  <div className="section-content">
                    {(() => {
                      const similarityMatches = formatSimilarityMatches(result.similarity_matches);
                      
                      return (
                        <div className="similarity-analysis">
                          {similarityMatches.length > 0 ? (
                            <div className="similarity-matches">
                              <h4>Similar Images Found</h4>
                              {similarityMatches.map((match, idx) => (
                                <div key={idx} className="similarity-match">
                                  <div className="match-info">
                                    <span className="match-id">Image ID: {match.image_id}</span>
                                    <span className="match-type">{match.image_type}</span>
                                  </div>
                                  <div className="similarity-score">
                                    {(match.similarity_score * 100).toFixed(1)}% similar
                                  </div>
                                </div>
                              ))}
                            </div>
                          ) : (
                            <div className="no-matches">
                              <LuEye className="unique-icon" />
                              <span>No similar images found - appears to be unique</span>
                            </div>
                          )}
                        </div>
                      );
                    })()}
                  </div>
                )}
              </div>

              {/* Processing Status */}
              <div className="processing-status">
                <LuClock className="status-icon" />
                <span>
                  Analysis completed at {new Date(result.processed_at || Date.now()).toLocaleString()}
                </span>
              </div>
            </div>
          ))}
        </div>

        <div className="analysis-footer">
          <div className="footer-info">
            <LuInfo className="info-icon" />
            <span>Analysis results are used for quality assurance and educational purposes</span>
          </div>
          <button className="primary-btn" onClick={onClose}>
            Continue
          </button>
        </div>
      </div>
    </div>
  );
};

export default ImageAnalysisResults;