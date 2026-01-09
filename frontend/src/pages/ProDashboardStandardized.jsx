import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { useTutorialAuth } from '../contexts/TutorialAuthContext';
import { 
  LuArrowLeft, 
  LuPlay, 
  LuUpload, 
  LuAward, 
  LuCheck, 
  LuX, 
  LuClock, 
  LuLock,
  LuVideo,
  LuImage,
  LuUsers
} from 'react-icons/lu';

const API_BASE = 'http://localhost/my_little_thingz/backend/api';

export default function ProDashboardStandardized() {
  const { tutorialAuth } = useTutorialAuth();
  const [progressData, setProgressData] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    if (tutorialAuth?.email) {
      fetchProgressData();
    }
  }, [tutorialAuth?.email]);

  const fetchProgressData = async () => {
    try {
      setLoading(true);
      const response = await fetch(`${API_BASE}/pro/learning-progress-standardized.php`, {
        headers: {
          'X-Tutorial-Email': tutorialAuth?.email || ''
        }
      });
      
      const data = await response.json();
      if (data.status === 'success') {
        setProgressData(data);
      } else {
        setError(data.message || 'Failed to fetch progress data');
      }
    } catch (error) {
      console.error('Error fetching progress:', error);
      setError('Failed to load progress data');
    } finally {
      setLoading(false);
    }
  };

  const getProgressColor = (percentage) => {
    if (percentage >= 80) return '#10B981'; // Green
    if (percentage >= 60) return '#F59E0B'; // Yellow
    if (percentage >= 40) return '#EF4444'; // Red
    return '#6B7280'; // Gray
  };

  const getPracticeStatusIcon = (status, uploaded, approved) => {
    if (!uploaded) {
      return <LuUpload size={16} className="text-gray-400" />;
    }
    
    switch (status) {
      case 'approved':
        return <LuCheck size={16} className="text-green-600" />;
      case 'rejected':
        return <LuX size={16} className="text-red-600" />;
      case 'pending':
      default:
        return <LuClock size={16} className="text-yellow-600" />;
    }
  };

  const getPracticeStatusText = (status, uploaded, approved) => {
    if (!uploaded) return 'Not Uploaded';
    
    switch (status) {
      case 'approved':
        return 'Practice Approved';
      case 'rejected':
        return 'Practice Rejected';
      case 'pending':
      default:
        return 'Pending Review';
    }
  };

  const handleCertificateDownload = async () => {
    if (!progressData?.certificate_rules?.eligible) {
      alert(`Certificate not available yet.\n\n${progressData?.certificate_rules?.message || 'Complete 80% of the course to unlock your certificate.'}`);
      return;
    }

    try {
      const response = await fetch(`${API_BASE}/pro/certificate-standardized.php?email=${tutorialAuth?.email}&format=pdf`);
      
      if (response.ok) {
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `Certificate_${tutorialAuth?.email}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
      } else {
        const errorData = await response.json();
        alert(`Certificate Error:\n\n${errorData.message || 'Unable to generate certificate'}`);
      }
    } catch (error) {
      console.error('Certificate download error:', error);
      alert('Failed to download certificate. Please try again.');
    }
  };

  if (loading) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mx-auto mb-4"></div>
          <p className="text-gray-600">Loading your progress...</p>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="min-h-screen bg-gray-50 flex items-center justify-center">
        <div className="text-center">
          <p className="text-red-600 mb-4">{error}</p>
          <button 
            onClick={fetchProgressData}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700"
          >
            Try Again
          </button>
        </div>
      </div>
    );
  }

  const overallProgress = progressData?.overall_progress?.completion_percentage || 0;
  const certificateEligible = progressData?.certificate_rules?.eligible || false;

  return (
    <div className="min-h-screen bg-gray-50">
      {/* Header */}
      <div className="bg-white shadow-sm border-b">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="flex items-center justify-between h-16">
            <div className="flex items-center">
              <Link 
                to="/tutorials" 
                className="flex items-center text-gray-600 hover:text-gray-900 mr-4"
              >
                <LuArrowLeft size={20} className="mr-2" />
                Back to Tutorials
              </Link>
              <h1 className="text-xl font-semibold text-gray-900">My Learning Progress</h1>
            </div>
          </div>
        </div>
      </div>

      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Overall Progress Card */}
        <div className="bg-white rounded-lg shadow-sm p-6 mb-8">
          <div className="flex items-center justify-between mb-6">
            <div>
              <h2 className="text-2xl font-bold text-gray-900">Overall Progress</h2>
              <p className="text-gray-600">Track your craft learning journey and earn certificates</p>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            {/* Progress Circle */}
            <div className="flex items-center justify-center">
              <div className="relative w-32 h-32">
                <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 36 36">
                  <path
                    className="text-gray-200"
                    stroke="currentColor"
                    strokeWidth="3"
                    fill="transparent"
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                  <path
                    className="text-blue-600"
                    stroke="currentColor"
                    strokeWidth="3"
                    fill="transparent"
                    strokeDasharray={`${overallProgress}, 100`}
                    d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                  />
                </svg>
                <div className="absolute inset-0 flex items-center justify-center">
                  <div className="text-center">
                    <div className="text-2xl font-bold text-gray-900">{Math.round(overallProgress)}%</div>
                    <div className="text-sm text-gray-600">Complete</div>
                  </div>
                </div>
              </div>
            </div>

            {/* Stats */}
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <span className="text-gray-600">Completed Tutorials</span>
                <span className="font-semibold">{progressData?.overall_progress?.completed_tutorials || 0}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-gray-600">Total Tutorials</span>
                <span className="font-semibold">{progressData?.overall_progress?.total_tutorials || 0}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-gray-600">Certificate Status</span>
                <span className={`font-semibold ${certificateEligible ? 'text-green-600' : 'text-gray-600'}`}>
                  {certificateEligible ? 'Available' : 'Locked'}
                </span>
              </div>
            </div>

            {/* Certificate Section */}
            <div className="bg-gray-50 rounded-lg p-4">
              <div className="text-center">
                <div className="mb-4">
                  {certificateEligible ? (
                    <LuAward size={48} className="mx-auto text-yellow-500" />
                  ) : (
                    <LuLock size={48} className="mx-auto text-gray-400" />
                  )}
                </div>
                <h3 className="font-semibold text-gray-900 mb-2">Certificate of Completion</h3>
                <p className="text-sm text-gray-600 mb-4">
                  {certificateEligible 
                    ? 'Congratulations! You can now download your certificate.'
                    : `Complete 80% of the course to unlock your certificate. (${Math.round(80 - overallProgress)}% remaining)`
                  }
                </p>
                <button
                  onClick={handleCertificateDownload}
                  disabled={!certificateEligible}
                  className={`w-full px-4 py-2 rounded-lg font-medium ${
                    certificateEligible
                      ? 'bg-yellow-500 text-white hover:bg-yellow-600'
                      : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                  }`}
                >
                  {certificateEligible ? 'Download Certificate' : 'Certificate Locked'}
                </button>
              </div>
            </div>
          </div>
        </div>

        {/* Tutorial Progress List */}
        <div className="bg-white rounded-lg shadow-sm">
          <div className="p-6 border-b border-gray-200">
            <h3 className="text-lg font-semibold text-gray-900">Tutorial Progress</h3>
            <p className="text-gray-600">
              {progressData?.tutorial_progress?.length || 0} of {progressData?.overall_progress?.total_tutorials || 0} tutorials completed
            </p>
          </div>

          <div className="divide-y divide-gray-200">
            {progressData?.tutorial_progress?.map((tutorial, index) => (
              <div key={tutorial.tutorial_id} className="p-6">
                <div className="flex items-center justify-between mb-4">
                  <div className="flex-1">
                    <h4 className="text-lg font-medium text-gray-900">{tutorial.title}</h4>
                    <p className="text-sm text-gray-600">{tutorial.category} • {tutorial.duration} min</p>
                  </div>
                  <div className="flex items-center space-x-4">
                    <span className="text-2xl font-bold" style={{ color: getProgressColor(tutorial.progress_percentage) }}>
                      {Math.round(tutorial.progress_percentage)}%
                    </span>
                    <Link
                      to={`/tutorial/${tutorial.tutorial_id}`}
                      className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center"
                    >
                      <LuPlay size={16} className="mr-2" />
                      View
                    </Link>
                  </div>
                </div>

                {/* Progress Bar */}
                <div className="mb-4">
                  <div className="w-full bg-gray-200 rounded-full h-2">
                    <div
                      className="h-2 rounded-full transition-all duration-300"
                      style={{
                        width: `${tutorial.progress_percentage}%`,
                        backgroundColor: getProgressColor(tutorial.progress_percentage)
                      }}
                    ></div>
                  </div>
                </div>

                {/* Progress Components */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  {/* Video Progress */}
                  <div className="flex items-center space-x-3">
                    <div className={`p-2 rounded-lg ${tutorial.video_completed ? 'bg-green-100' : 'bg-gray-100'}`}>
                      <LuVideo size={20} className={tutorial.video_completed ? 'text-green-600' : 'text-gray-400'} />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">Video</p>
                      <p className={`text-sm ${tutorial.video_completed ? 'text-green-600' : 'text-gray-600'}`}>
                        {tutorial.video_completed ? 'Completed' : 'Not Watched'}
                      </p>
                    </div>
                  </div>

                  {/* Practice Progress */}
                  <div className="flex items-center space-x-3">
                    <div className={`p-2 rounded-lg ${
                      tutorial.practice_approved ? 'bg-green-100' : 
                      tutorial.practice_uploaded ? 'bg-yellow-100' : 'bg-gray-100'
                    }`}>
                      {getPracticeStatusIcon(tutorial.practice_status, tutorial.practice_uploaded, tutorial.practice_approved)}
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">Practice</p>
                      <p className={`text-sm ${
                        tutorial.practice_approved ? 'text-green-600' : 
                        tutorial.practice_uploaded ? 'text-yellow-600' : 'text-gray-600'
                      }`}>
                        {getPracticeStatusText(tutorial.practice_status, tutorial.practice_uploaded, tutorial.practice_approved)}
                      </p>
                    </div>
                  </div>

                  {/* Live Session Progress */}
                  <div className="flex items-center space-x-3">
                    <div className={`p-2 rounded-lg ${tutorial.live_session_completed ? 'bg-green-100' : 'bg-gray-100'}`}>
                      <LuUsers size={20} className={tutorial.live_session_completed ? 'text-green-600' : 'text-gray-400'} />
                    </div>
                    <div>
                      <p className="font-medium text-gray-900">Live Session</p>
                      <p className={`text-sm ${tutorial.live_session_completed ? 'text-green-600' : 'text-gray-600'}`}>
                        {tutorial.live_session_completed ? 'Completed' : 'Not Attended'}
                      </p>
                    </div>
                  </div>
                </div>

                {/* Admin Feedback */}
                {tutorial.admin_feedback && (
                  <div className="mt-4 p-3 bg-blue-50 rounded-lg">
                    <p className="text-sm font-medium text-blue-900">Instructor Feedback:</p>
                    <p className="text-sm text-blue-800">{tutorial.admin_feedback}</p>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Progress Rules */}
        <div className="mt-8 bg-blue-50 rounded-lg p-6">
          <h3 className="text-lg font-semibold text-blue-900 mb-4">Progress Rules</h3>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-blue-800">
            <div>
              <p><strong>Video:</strong> {progressData?.progress_rules?.video}</p>
              <p><strong>Practice:</strong> {progressData?.progress_rules?.practice}</p>
            </div>
            <div>
              <p><strong>Live Session:</strong> {progressData?.progress_rules?.live_session}</p>
              <p><strong>Certificate:</strong> {progressData?.progress_rules?.certificate}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}