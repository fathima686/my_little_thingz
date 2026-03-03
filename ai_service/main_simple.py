"""
AI Image Generation Service - Simplified Version (Without Stable Diffusion)
This version only uses Gemini for prompt refinement and returns a placeholder
Use this for testing the API structure before installing heavy ML dependencies
"""

from fastapi import FastAPI, HTTPException
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
import os
from pathlib import Path
import logging

from gemini_prompt import refine_prompt_with_gemini

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialize FastAPI app
app = FastAPI(
    title="AI Image Generation Service (Simplified)",
    description="Gemini-powered prompt refinement (Stable Diffusion disabled for testing)",
    version="1.0.0-simple"
)

# CORS configuration
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Request/Response models
class ImageGenerationRequest(BaseModel):
    prompt: str

class ImageGenerationResponse(BaseModel):
    image_url: str
    refined_prompt: str
    original_prompt: str
    note: str

@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "status": "online",
        "service": "AI Image Generation Service (Simplified)",
        "note": "Stable Diffusion not installed. Install torch, diffusers, transformers for full functionality.",
        "endpoints": {
            "generate": "/generate-image",
            "health": "/health"
        }
    }

@app.get("/health")
async def health_check():
    """Detailed health check"""
    return {
        "status": "healthy",
        "gemini": "configured",
        "stable_diffusion": "not installed (use main.py after installing ML dependencies)",
        "mode": "simplified"
    }

@app.post("/generate-image", response_model=ImageGenerationResponse)
async def generate_image(request: ImageGenerationRequest):
    """
    Simplified endpoint - only refines prompt, doesn't generate image
    """
    try:
        # Validate prompt
        if not request.prompt or len(request.prompt.strip()) == 0:
            raise HTTPException(
                status_code=400,
                detail="Prompt cannot be empty"
            )
        
        if len(request.prompt) > 500:
            raise HTTPException(
                status_code=400,
                detail="Prompt too long. Maximum 500 characters."
            )
        
        logger.info(f"Received prompt: {request.prompt}")
        
        # Refine prompt using Gemini
        logger.info("Refining prompt with Gemini...")
        refined_prompt = refine_prompt_with_gemini(request.prompt)
        logger.info(f"Refined prompt: {refined_prompt}")
        
        # Return placeholder response
        return ImageGenerationResponse(
            image_url="https://via.placeholder.com/512x512.png?text=Install+ML+Dependencies",
            refined_prompt=refined_prompt,
            original_prompt=request.prompt,
            note="This is a placeholder. Install torch, diffusers, and transformers to generate real images."
        )
        
    except ValueError as ve:
        logger.error(f"Validation error: {str(ve)}")
        raise HTTPException(status_code=400, detail=str(ve))
    
    except Exception as e:
        logger.error(f"Error: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Failed to process request: {str(e)}"
        )

if __name__ == "__main__":
    import uvicorn
    
    print("\n" + "="*60)
    print("AI IMAGE GENERATION SERVICE - SIMPLIFIED MODE")
    print("="*60)
    print("\nThis version only tests Gemini prompt refinement.")
    print("To enable image generation, install ML dependencies:")
    print("  pip install torch diffusers transformers accelerate")
    print("\nThen use: python main.py")
    print("="*60 + "\n")
    
    uvicorn.run(
        "main_simple:app",
        host="0.0.0.0",
        port=8001,
        reload=True,
        log_level="info"
    )
