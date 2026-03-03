"""
AI Image Generation Service - FastAPI Backend
Integrates Gemini prompt refinement with Stable Diffusion image generation
"""

from fastapi import FastAPI, HTTPException, File, UploadFile, Form
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from pydantic import BaseModel
from typing import Optional
import os
from pathlib import Path
import logging

from gemini_prompt import refine_prompt_with_gemini
from diffusion_engine import generate_image_with_diffusion
from img2img_engine import convert_image_to_style, get_available_styles, get_style_info

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialize FastAPI app
app = FastAPI(
    title="AI Image Generation Service",
    description="Gemini-powered prompt refinement + Stable Diffusion image generation",
    version="1.0.0"
)

# CORS configuration - allow frontend to call this service
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # In production, specify your frontend domain
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Create generated_images directory if it doesn't exist
IMAGES_DIR = Path("generated_images")
IMAGES_DIR.mkdir(exist_ok=True)

# Mount static files to serve generated images
app.mount("/images", StaticFiles(directory="generated_images"), name="images")

# Request/Response models
class ImageGenerationRequest(BaseModel):
    prompt: str

class ImageGenerationResponse(BaseModel):
    image_url: str
    refined_prompt: str
    original_prompt: str

class CartoonConversionResponse(BaseModel):
    image_url: str
    style: str
    original_filename: str

@app.get("/")
async def root():
    """Health check endpoint"""
    return {
        "status": "online",
        "service": "AI Image Generation Service",
        "endpoints": {
            "generate": "/generate-image",
            "convert": "/convert-to-cartoon",
            "styles": "/styles",
            "health": "/health"
        }
    }

@app.get("/health")
async def health_check():
    """Detailed health check"""
    return {
        "status": "healthy",
        "gemini": "configured",
        "stable_diffusion": "ready",
        "img2img": "ready",
        "images_directory": str(IMAGES_DIR.absolute())
    }

@app.post("/generate-image", response_model=ImageGenerationResponse)
async def generate_image(request: ImageGenerationRequest):
    """
    Main endpoint for AI image generation
    
    Flow:
    1. Validate input prompt
    2. Refine prompt using Gemini API
    3. Generate image using Stable Diffusion
    4. Save image locally
    5. Return image URL
    
    Args:
        request: ImageGenerationRequest with prompt text
        
    Returns:
        ImageGenerationResponse with image_url and refined_prompt
    """
    try:
        # Step 1: Validate prompt
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
        
        # Step 2: Refine prompt using Gemini
        logger.info("Refining prompt with Gemini...")
        refined_prompt = refine_prompt_with_gemini(request.prompt)
        logger.info(f"Refined prompt: {refined_prompt}")
        
        # Step 3: Generate image using Stable Diffusion
        logger.info("Generating image with Stable Diffusion...")
        image_filename = generate_image_with_diffusion(refined_prompt)
        logger.info(f"Image generated: {image_filename}")
        
        # Step 4: Construct image URL
        # This URL will be accessible via the /images static mount
        image_url = f"http://localhost:8001/images/{image_filename}"
        
        # Step 5: Return response
        return ImageGenerationResponse(
            image_url=image_url,
            refined_prompt=refined_prompt,
            original_prompt=request.prompt
        )
        
    except ValueError as ve:
        logger.error(f"Validation error: {str(ve)}")
        raise HTTPException(status_code=400, detail=str(ve))
    
    except Exception as e:
        logger.error(f"Error generating image: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Failed to generate image: {str(e)}"
        )

@app.get("/styles")
async def get_styles():
    """
    Get available conversion styles
    
    Returns:
        Dictionary with style names and descriptions
    """
    try:
        styles = get_style_info()
        return {
            "status": "success",
            "styles": styles,
            "available_styles": list(styles.keys())
        }
    except Exception as e:
        logger.error(f"Error getting styles: {str(e)}")
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/convert-to-cartoon", response_model=CartoonConversionResponse)
async def convert_to_cartoon(
    image: UploadFile = File(..., description="Image file to convert"),
    style: str = Form("cartoon", description="Style to apply: cartoon, anime, watercolor, pencil_sketch"),
    strength: Optional[float] = Form(None, description="Denoising strength (0.0-1.0, default: 0.55)")
):
    """
    Convert an uploaded image to cartoon/artistic style using Stable Diffusion img2img
    
    Flow:
    1. Validate uploaded image
    2. Read image bytes
    3. Apply style transformation using img2img
    4. Save converted image
    5. Return image URL
    
    Args:
        image: Uploaded image file (PNG, JPG, JPEG)
        style: Style to apply (cartoon, anime, watercolor, pencil_sketch)
        strength: Optional denoising strength (0.0-1.0)
        
    Returns:
        CartoonConversionResponse with image_url and style info
    """
    try:
        # Step 1: Validate file type
        allowed_types = ["image/png", "image/jpeg", "image/jpg"]
        if image.content_type not in allowed_types:
            raise HTTPException(
                status_code=400,
                detail=f"Invalid file type. Allowed: PNG, JPG, JPEG. Got: {image.content_type}"
            )
        
        # Validate file size (max 10MB)
        max_size = 10 * 1024 * 1024  # 10MB
        image_bytes = await image.read()
        
        if len(image_bytes) > max_size:
            raise HTTPException(
                status_code=400,
                detail=f"File too large. Maximum size: 10MB. Got: {len(image_bytes) / 1024 / 1024:.2f}MB"
            )
        
        if len(image_bytes) == 0:
            raise HTTPException(
                status_code=400,
                detail="Empty file uploaded"
            )
        
        logger.info(f"Received image: {image.filename} ({len(image_bytes)} bytes)")
        logger.info(f"Style: {style}, Strength: {strength}")
        
        # Step 2: Validate style
        available_styles = get_available_styles()
        if style not in available_styles:
            raise HTTPException(
                status_code=400,
                detail=f"Invalid style. Available: {', '.join(available_styles)}"
            )
        
        # Step 3: Convert image using img2img
        logger.info(f"Converting image to {style} style...")
        converted_filename = convert_image_to_style(
            image_bytes=image_bytes,
            style=style,
            strength=strength
        )
        logger.info(f"Image converted: {converted_filename}")
        
        # Step 4: Construct image URL
        image_url = f"http://localhost:8001/images/{converted_filename}"
        
        # Step 5: Return response
        return CartoonConversionResponse(
            image_url=image_url,
            style=style,
            original_filename=image.filename
        )
        
    except HTTPException:
        # Re-raise HTTP exceptions as-is
        raise
    
    except ValueError as ve:
        logger.error(f"Validation error: {str(ve)}")
        raise HTTPException(status_code=400, detail=str(ve))
    
    except Exception as e:
        logger.error(f"Error converting image: {str(e)}")
        raise HTTPException(
            status_code=500,
            detail=f"Failed to convert image: {str(e)}"
        )

@app.delete("/images/{filename}")
async def delete_image(filename: str):
    """Optional: Delete a generated image"""
    try:
        image_path = IMAGES_DIR / filename
        if image_path.exists():
            image_path.unlink()
            return {"status": "deleted", "filename": filename}
        else:
            raise HTTPException(status_code=404, detail="Image not found")
    except Exception as e:
        raise HTTPException(status_code=500, detail=str(e))

if __name__ == "__main__":
    import uvicorn
    
    # Run the FastAPI server
    # Access at: http://localhost:8001
    uvicorn.run(
        "main:app",
        host="0.0.0.0",
        port=8001,
        reload=True,  # Auto-reload on code changes during development
        log_level="info"
    )
