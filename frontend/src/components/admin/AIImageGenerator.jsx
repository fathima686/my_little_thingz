/**
 * AI Image Generator Component
 * Integrates with AI Image Generation Service
 * Can be used standalone or embedded in TemplateEditor
 */

import React, { useState } from 'react';
import { LuSparkles, LuX, LuLoader2 } from 'react-icons/lu';

const AI_SERVICE_URL = "http://localhost:8001";

export default function AIImageGenerator({ onImageGenerated, isOpen, onClose }) {
  const [prompt, setPrompt] = useState('');
  const [isGenerating, setIsGenerating] = useState(false);
  const [error, setError] = useState(null);
  const [refinedPrompt, setRefinedPrompt] = useState('');
  const [generatedImageUrl, setGeneratedImageUrl] = useState('');

  const handleGenerate = async () => {
    // Validation
    if (!prompt.trim()) {
      setError('Please enter a prompt');
      return;
    }

    if (prompt.length > 500) {
      setError('Prompt too long. Maximum 500 characters.');
      return;
    }

    try {
      setIsGenerating(true);
      setError(null);
      setRefinedPrompt('');
      setGeneratedImageUrl('');

      // Call AI service
      const response = await fetch(`${AI_SERVICE_URL}/generate-image`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({ prompt: prompt.trim() }),
        signal: AbortSignal.timeout(300000) // 5 minute timeout
      });

      if (!response.ok) {
        const errorData = await response.json();
        throw new Error(errorData.detail || 'Failed to generate image');
      }

      const data = await response.json();

      // Update state
      setRefinedPrompt(data.refined_prompt);
      setGeneratedImageUrl(data.image_url);

      // Callback to parent component (e.g., TemplateEditor)
      if (onImageGenerated) {
        onImageGenerated(data.image_url, data.refined_prompt);
      }

    } catch (err) {
      if (err.name === 'TimeoutError') {
        setError('Generation timed out. Please try a simpler prompt or try again.');
      } else if (err.name === 'AbortError') {
        setError('Request was cancelled.');
      } else {
        setError(err.message || 'Failed to generate image');
      }
      console.error('AI Image Generation Error:', err);
    } finally {
      setIsGenerating(false);
    }
  };

  const handleClose = () => {
    if (!isGenerating) {
      setPrompt('');
      setError(null);
      setRefinedPrompt('');
      setGeneratedImageUrl('');
      if (onClose) onClose();
    }
  };

  const handleKeyPress = (e) => {
    if (e.key === 'Enter' && e.ctrlKey && !isGenerating) {
      handleGenerate();
    }
  };

  if (!isOpen) return null;

  return (
    <div className="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
      <div className="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        {/* Header */}
        <div className="flex items-center justify-between p-6 border-b">
          <div className="flex items-center space-x-2">
            <LuSparkles className="w-6 h-6 text-purple-600" />
            <h2 className="text-xl font-semibold">Generate AI Image</h2>
          </div>
          <button
            onClick={handleClose}
            disabled={isGenerating}
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors disabled:opacity-50"
            title="Close"
          >
            <LuX className="w-5 h-5" />
          </button>
        </div>

        {/* Content */}
        <div className="p-6 space-y-4">
          {/* Prompt Input */}
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Describe the image you want to generate
            </label>
            <textarea
              value={prompt}
              onChange={(e) => setPrompt(e.target.value)}
              onKeyPress={handleKeyPress}
              placeholder="Example: a golden trophy on a pedestal, professional certificate border, abstract geometric pattern..."
              className="w-full h-32 p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
              disabled={isGenerating}
            />
            <div className="flex items-center justify-between mt-1">
              <p className="text-xs text-gray-500">
                Tip: Be specific! Describe style, colors, and composition.
              </p>
              <p className="text-xs text-gray-500">
                {prompt.length}/500
              </p>
            </div>
          </div>

          {/* Error Message */}
          {error && (
            <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
              <p className="text-sm text-red-700">{error}</p>
            </div>
          )}

          {/* Generating Status */}
          {isGenerating && (
            <div className="p-4 bg-purple-50 border border-purple-200 rounded-lg">
              <div className="flex items-center space-x-3">
                <LuLoader2 className="w-5 h-5 text-purple-600 animate-spin" />
                <div>
                  <p className="text-sm font-medium text-purple-900">
                    Generating your image...
                  </p>
                  <p className="text-xs text-purple-700 mt-1">
                    This may take 30-90 seconds. Please wait.
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Refined Prompt Display */}
          {refinedPrompt && !isGenerating && (
            <div className="p-3 bg-blue-50 border border-blue-200 rounded-lg">
              <p className="text-xs font-medium text-blue-900 mb-1">
                AI-Refined Prompt:
              </p>
              <p className="text-xs text-blue-700">{refinedPrompt}</p>
            </div>
          )}

          {/* Generated Image Preview */}
          {generatedImageUrl && !isGenerating && (
            <div className="border border-gray-300 rounded-lg p-4">
              <p className="text-sm font-medium text-gray-700 mb-2">
                Generated Image:
              </p>
              <img
                src={generatedImageUrl}
                alt="AI Generated"
                className="w-full rounded-lg"
              />
            </div>
          )}

          {/* Example Prompts */}
          {!isGenerating && !generatedImageUrl && (
            <div className="p-3 bg-gray-50 rounded-lg">
              <p className="text-xs font-medium text-gray-700 mb-2">
                Example Prompts:
              </p>
              <div className="space-y-1">
                {[
                  "a golden trophy on a marble pedestal",
                  "elegant certificate border with floral design",
                  "abstract geometric pattern in blue and gold",
                  "minimalist mountain landscape silhouette",
                  "professional handshake illustration"
                ].map((example, index) => (
                  <button
                    key={index}
                    onClick={() => setPrompt(example)}
                    className="block w-full text-left text-xs text-gray-600 hover:text-purple-600 hover:bg-white px-2 py-1 rounded transition-colors"
                  >
                    • {example}
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="flex items-center justify-between p-6 border-t bg-gray-50">
          <div className="text-xs text-gray-500">
            {isGenerating ? (
              <span>Powered by Gemini + Stable Diffusion</span>
            ) : (
              <span>Press Ctrl+Enter to generate</span>
            )}
          </div>
          <div className="flex space-x-2">
            <button
              onClick={handleClose}
              disabled={isGenerating}
              className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors disabled:opacity-50"
            >
              {generatedImageUrl ? 'Close' : 'Cancel'}
            </button>
            <button
              onClick={handleGenerate}
              disabled={isGenerating || !prompt.trim()}
              className="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors disabled:opacity-50 flex items-center space-x-2"
            >
              {isGenerating ? (
                <>
                  <LuLoader2 className="w-4 h-4 animate-spin" />
                  <span>Generating...</span>
                </>
              ) : (
                <>
                  <LuSparkles className="w-4 h-4" />
                  <span>Generate</span>
                </>
              )}
            </button>
          </div>
        </div>
      </div>
    </div>
  );
}

/**
 * Usage Example in TemplateEditor.jsx:
 * 
 * import AIImageGenerator from './AIImageGenerator';
 * 
 * // Add state
 * const [showAIGenerator, setShowAIGenerator] = useState(false);
 * 
 * // Add button to toolbar
 * <ToolButton
 *   icon={LuSparkles}
 *   onClick={() => setShowAIGenerator(true)}
 *   tooltip="Generate AI Image"
 * />
 * 
 * // Add component
 * <AIImageGenerator
 *   isOpen={showAIGenerator}
 *   onClose={() => setShowAIGenerator(false)}
 *   onImageGenerated={(imageUrl, refinedPrompt) => {
 *     addImage(imageUrl);
 *     setShowAIGenerator(false);
 *   }}
 * />
 */
