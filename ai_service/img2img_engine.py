"""
Stable Diffusion Image-to-Image (img2img) Module
Converts uploaded images to different artistic styles using Stable Diffusion
"""

import torch
from diffusers import StableDiffusionImg2ImgPipeline, DPMSolverMultistepScheduler
from PIL import Image
import io
from pathlib import Path
from datetime import datetime
import logging

logger = logging.getLogger(__name__)

# Configuration
MODEL_ID = "runwayml/stable-diffusion-v1-5"
IMAGES_DIR = Path("generated_images")
IMAGE_SIZE = 512  # Resize input to 512x512 for consistency
NUM_INFERENCE_STEPS = 50  # More steps for better quality in img2img
GUIDANCE_SCALE = 7.5
DENOISING_STRENGTH = 0.55  # Balance between preserving structure and applying style

# Global img2img pipeline variable (loaded once)
_img2img_pipeline = None

# Style-specific prompts
STYLE_PROMPTS = {
    "cartoon": {
        "prompt": (
            "cartoon illustration style, smooth outlines, flat colors, "
            "soft shading, clean edges, vibrant colors, animated style, "
            "professional cartoon art, high quality, no text, no watermark"
        ),
        "negative": (
            "realistic, photorealistic, photograph, photo, text, watermark, "
            "signature, logo, words, letters, typography, blurry, distorted, "
            "low quality, ugly, deformed"
        )
    },
    "anime": {
        "prompt": (
            "anime style illustration, clean line art, expressive features, "
            "soft lighting, vibrant colors, studio ghibli inspired, "
            "japanese animation style, cel shaded, high quality anime art, "
            "no text, no watermark"
        ),
        "negative": (
            "realistic, western cartoon, 3d render, text, watermark, "
            "signature, logo, words, letters, blurry, distorted, "
            "low quality, ugly, deformed, bad anatomy"
        )
    },
    "watercolor": {
        "prompt": (
            "watercolor painting style, soft brush strokes, pastel colors, "
            "artistic texture, paper texture, flowing colors, gentle blending, "
            "artistic watercolor illustration, high quality, no text, no watermark"
        ),
        "negative": (
            "photograph, digital art, sharp edges, text, watermark, "
            "signature, logo, words, letters, blurry, distorted, "
            "low quality, ugly, deformed"
        )
    },
    "pencil_sketch": {
        "prompt": (
            "pencil sketch drawing, hand drawn lines, graphite texture, "
            "black and white, paper texture, artistic sketch, detailed linework, "
            "professional pencil art, high quality, no text, no watermark"
        ),
        "negative": (
            "color, colored, photograph, digital art, text, watermark, "
            "signature, logo, words, letters, blurry, distorted, "
            "low quality, ugly, deformed"
        )
    }
}

def load_img2img_model():
    """
    Load Stable Diffusion img2img model into memory
    This is called once and cached for subsequent conversions
    
    Returns:
        StableDiffusionImg2ImgPipeline instance
    """
    global _img2img_pipeline
    
    if _img2img_pipeline is not None:
        return _img2img_pipeline
    
    logger.info("Loading Stable Diffusion img2img model...")
    
    try:
        # Determine device (CUDA if available, else CPU)
        device = "cuda" if torch.cuda.is_available() else "cpu"
        logger.info(f"Using device: {device}")
        
        # Load the img2img model
        pipe = StableDiffusionImg2ImgPipeline.from_pretrained(
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
        
        _img2img_pipeline = pipe
        logger.info("✓ Stable Diffusion img2img model loaded successfully")
        
        return _img2img_pipeline
        
    except Exception as e:
        logger.error(f"Failed to load img2img model: {str(e)}")
        raise

def preprocess_image(image: Image.Image) -> Image.Image:
    """
    Preprocess uploaded image for img2img conversion
    
    Args:
        image: PIL Image object
        
    Returns:
        Preprocessed PIL Image (resized, RGB mode)
    """
    # Convert to RGB if needed (handles RGBA, grayscale, etc.)
    if image.mode != 'RGB':
        image = image.convert('RGB')
    
    # Resize to 512x512 while maintaining aspect ratio
    image.thumbnail((IMAGE_SIZE, IMAGE_SIZE), Image.Resampling.LANCZOS)
    
    # Create a new 512x512 image with white background
    new_image = Image.new('RGB', (IMAGE_SIZE, IMAGE_SIZE), (255, 255, 255))
    
    # Paste the resized image centered
    offset = ((IMAGE_SIZE - image.width) // 2, (IMAGE_SIZE - image.height) // 2)
    new_image.paste(image, offset)
    
    return new_image

def convert_image_to_style(
    image_bytes: bytes,
    style: str = "cartoon",
    strength: float = None
) -> str:
    """
    Convert an uploaded image to a specific artistic style using img2img
    
    Args:
        image_bytes: Raw image bytes from upload
        style: Style to apply (cartoon, anime, watercolor, pencil_sketch)
        strength: Denoising strength (0.0-1.0). If None, uses default.
                 Higher = more transformation, Lower = more preservation
        
    Returns:
        Filename of the converted image (saved in generated_images/)
        
    Process:
    1. Load img2img model (if not already loaded)
    2. Preprocess input image
    3. Get style-specific prompts
    4. Apply img2img transformation
    5. Save result with unique filename
    6. Return filename
    """
    
    try:
        # Step 1: Load model
        pipe = load_img2img_model()
        
        # Step 2: Load and preprocess image
        logger.info("Preprocessing input image...")
        input_image = Image.open(io.BytesIO(image_bytes))
        processed_image = preprocess_image(input_image)
        
        # Step 3: Get style prompts
        if style not in STYLE_PROMPTS:
            logger.warning(f"Unknown style '{style}', defaulting to 'cartoon'")
            style = "cartoon"
        
        style_config = STYLE_PROMPTS[style]
        prompt = style_config["prompt"]
        negative_prompt = style_config["negative"]
        
        logger.info(f"Converting image to {style} style...")
        logger.info(f"Prompt: {prompt[:100]}...")
        
        # Step 4: Apply img2img transformation
        # Use provided strength or default
        denoising_strength = strength if strength is not None else DENOISING_STRENGTH
        
        # Ensure strength is in valid range
        denoising_strength = max(0.0, min(1.0, denoising_strength))
        
        with torch.no_grad():  # Disable gradient calculation for inference
            result = pipe(
                prompt=prompt,
                negative_prompt=negative_prompt,
                image=processed_image,
                strength=denoising_strength,  # How much to transform (0.0-1.0)
                num_inference_steps=NUM_INFERENCE_STEPS,
                guidance_scale=GUIDANCE_SCALE,
            )
        
        # Extract the converted image
        converted_image = result.images[0]
        
        # Step 5: Save image with unique filename
        timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
        filename = f"cartoon_{style}_{timestamp}.png"
        filepath = IMAGES_DIR / filename
        
        # Ensure directory exists
        IMAGES_DIR.mkdir(exist_ok=True)
        
        # Save as PNG
        converted_image.save(filepath, format="PNG", optimize=True)
        
        logger.info(f"✓ Image converted and saved: {filename}")
        
        # Step 6: Return filename
        return filename
        
    except Exception as e:
        logger.error(f"Error converting image: {str(e)}")
        raise ValueError(f"Image conversion failed: {str(e)}")

def get_available_styles():
    """
    Get list of available conversion styles
    
    Returns:
        List of style names
    """
    return list(STYLE_PROMPTS.keys())

def get_style_info():
    """
    Get information about all available styles
    
    Returns:
        Dictionary with style descriptions
    """
    return {
        "cartoon": "Smooth cartoon illustration with flat colors and clean outlines",
        "anime": "Japanese anime style with clean line art and expressive features",
        "watercolor": "Soft watercolor painting with artistic brush strokes",
        "pencil_sketch": "Hand-drawn pencil sketch in black and white"
    }

if __name__ == "__main__":
    # Test the module
    print("Image-to-Image Conversion Styles:")
    styles = get_style_info()
    for style, description in styles.items():
        print(f"  {style}: {description}")
