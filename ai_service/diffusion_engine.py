"""
Stable Diffusion Image Generation Module
Generates images using open-source Stable Diffusion model
"""

import torch
from diffusers import StableDiffusionPipeline, DPMSolverMultistepScheduler
from PIL import Image
import os
from pathlib import Path
from datetime import datetime
import logging

logger = logging.getLogger(__name__)

# Configuration
MODEL_ID = "runwayml/stable-diffusion-v1-5"  # Free, open-source model
IMAGES_DIR = Path("generated_images")
IMAGE_SIZE = 512  # 512x512 for performance
NUM_INFERENCE_STEPS = 30  # Balance between quality and speed
GUIDANCE_SCALE = 7.5  # How closely to follow the prompt

# Global pipeline variable (loaded once)
_pipeline = None

def load_diffusion_model():
    """
    Load Stable Diffusion model into memory
    This is called once and cached for subsequent generations
    
    Returns:
        StableDiffusionPipeline instance
    """
    global _pipeline
    
    if _pipeline is not None:
        return _pipeline
    
    logger.info("Loading Stable Diffusion model (this may take a few minutes on first run)...")
    
    try:
        # Determine device (CUDA if available, else CPU)
        device = "cuda" if torch.cuda.is_available() else "cpu"
        logger.info(f"Using device: {device}")
        
        # Load the model
        pipe = StableDiffusionPipeline.from_pretrained(
            MODEL_ID,
            torch_dtype=torch.float16 if device == "cuda" else torch.float32,
            safety_checker=None,  # Disable for academic use
            requires_safety_checker=False
        )
        
        # Use DPM-Solver++ scheduler for faster generation
        pipe.scheduler = DPMSolverMultistepScheduler.from_config(pipe.scheduler.config)
        
        # Move to device
        pipe = pipe.to(device)
        
        # Enable memory optimizations for CPU/low-memory systems
        if device == "cpu":
            pipe.enable_attention_slicing()
        
        _pipeline = pipe
        logger.info("✓ Stable Diffusion model loaded successfully")
        
        return _pipeline
        
    except Exception as e:
        logger.error(f"Failed to load Stable Diffusion model: {str(e)}")
        raise

def generate_image_with_diffusion(prompt: str) -> str:
    """
    Generate an image using Stable Diffusion
    
    Args:
        prompt: Refined text prompt (from Gemini)
        
    Returns:
        Filename of the generated image (saved in generated_images/)
        
    Process:
    1. Load model (if not already loaded)
    2. Generate image from prompt
    3. Save image with unique filename
    4. Return filename
    """
    
    try:
        # Step 1: Load model
        pipe = load_diffusion_model()
        
        # Step 2: Generate image
        logger.info(f"Generating image for prompt: {prompt[:100]}...")
        
        # Add negative prompt to avoid unwanted elements
        negative_prompt = (
            "text, watermark, signature, logo, words, letters, typography, "
            "low quality, blurry, distorted, deformed, ugly, bad anatomy, "
            "extra limbs, poorly drawn, amateur"
        )
        
        # Generate the image
        with torch.no_grad():  # Disable gradient calculation for inference
            result = pipe(
                prompt=prompt,
                negative_prompt=negative_prompt,
                num_inference_steps=NUM_INFERENCE_STEPS,
                guidance_scale=GUIDANCE_SCALE,
                height=IMAGE_SIZE,
                width=IMAGE_SIZE,
            )
        
        # Extract the generated image
        image = result.images[0]
        
        # Step 3: Save image with unique filename
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"ai_generated_{timestamp}.png"
        filepath = IMAGES_DIR / filename
        
        # Ensure directory exists
        IMAGES_DIR.mkdir(exist_ok=True)
        
        # Save as PNG
        image.save(filepath, format="PNG", optimize=True)
        
        logger.info(f"✓ Image saved: {filename}")
        
        # Step 4: Return filename
        return filename
        
    except Exception as e:
        logger.error(f"Error generating image: {str(e)}")
        raise ValueError(f"Image generation failed: {str(e)}")

def test_image_generation():
    """
    Test function to verify Stable Diffusion is working
    """
    try:
        test_prompt = (
            "A simple red apple on a white background, "
            "professional product photography, no text, no watermark, "
            "high quality, clean, printable design"
        )
        
        print("Testing Stable Diffusion image generation...")
        print(f"Prompt: {test_prompt}")
        print("This may take 1-3 minutes on CPU...")
        
        filename = generate_image_with_diffusion(test_prompt)
        
        print(f"✓ Image generated successfully: {filename}")
        print(f"  Location: {IMAGES_DIR / filename}")
        
        return True
        
    except Exception as e:
        print(f"✗ Image generation failed: {str(e)}")
        return False

def get_model_info():
    """
    Get information about the loaded model
    """
    return {
        "model_id": MODEL_ID,
        "image_size": f"{IMAGE_SIZE}x{IMAGE_SIZE}",
        "inference_steps": NUM_INFERENCE_STEPS,
        "guidance_scale": GUIDANCE_SCALE,
        "device": "cuda" if torch.cuda.is_available() else "cpu",
        "cuda_available": torch.cuda.is_available()
    }

if __name__ == "__main__":
    # Test the module
    print("Stable Diffusion Configuration:")
    info = get_model_info()
    for key, value in info.items():
        print(f"  {key}: {value}")
    
    print("\n" + "="*50)
    test_image_generation()
