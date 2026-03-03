"""
Gemini Prompt Refinement Module
Uses Google's Gemini API to intelligently refine user prompts for image generation
"""

import os
import google.generativeai as genai
from dotenv import load_dotenv
import logging

logger = logging.getLogger(__name__)

# Load environment variables
load_dotenv()

# Configure Gemini API
GEMINI_API_KEY = os.getenv("GEMINI_API_KEY")
if not GEMINI_API_KEY:
    raise ValueError("GEMINI_API_KEY not found in .env file")

genai.configure(api_key=GEMINI_API_KEY)

# Initialize Gemini model
# Using gemini-pro for text generation
model = genai.GenerativeModel('gemini-pro')

def refine_prompt_with_gemini(user_prompt: str) -> str:
    """
    Refine a user's image generation prompt using Gemini AI
    
    Purpose:
    - Improve vague or weak prompts
    - Add artistic style and quality constraints
    - Ensure clean, professional design output
    - Remove ambiguity
    
    Args:
        user_prompt: Raw user input (e.g., "a cat")
        
    Returns:
        Refined prompt optimized for Stable Diffusion
        
    Example:
        Input: "a cat"
        Output: "A professional photograph of a fluffy orange cat sitting elegantly, 
                clean white background, no text, no watermark, high quality, 
                studio lighting, printable design"
    """
    
    # System instruction for Gemini
    system_instruction = """
You are an expert prompt engineer for AI image generation systems.

Your task is to refine user prompts to create high-quality, professional images suitable for templates and certificates.

RULES:
1. Keep the core subject from the user's prompt
2. Add artistic style and quality descriptors
3. ALWAYS include these constraints:
   - "clean background" or "white background" or "transparent background"
   - "no text in image"
   - "no watermark"
   - "high quality"
   - "printable design"
4. Add lighting and composition details
5. Keep the refined prompt under 150 words
6. Make it specific and descriptive
7. Use professional photography or illustration terminology
8. Ensure the output is suitable for business/professional use

OUTPUT FORMAT:
Return ONLY the refined prompt text. No explanations, no quotes, no extra text.

EXAMPLES:

User: "a cat"
Refined: "A professional photograph of a fluffy orange tabby cat sitting elegantly on a pedestal, clean white background, soft studio lighting, no text, no watermark, high quality, sharp focus, printable design, commercial photography style"

User: "mountain landscape"
Refined: "A majestic mountain landscape with snow-capped peaks under a clear blue sky, professional nature photography, clean composition, no text overlay, no watermark, high resolution, printable quality, vibrant colors, golden hour lighting"

User: "business handshake"
Refined: "Professional business handshake between two people in formal attire, clean white background, corporate photography style, no text, no watermark, high quality, well-lit, suitable for certificates and professional documents"

Now refine the following prompt:
"""
    
    try:
        # Construct the full prompt
        full_prompt = f"{system_instruction}\n\nUser prompt: \"{user_prompt}\"\n\nRefined prompt:"
        
        # Generate refined prompt using Gemini
        response = model.generate_content(full_prompt)
        
        # Extract refined prompt from response
        refined_prompt = response.text.strip()
        
        # Validation: Ensure refined prompt is not empty
        if not refined_prompt:
            logger.warning("Gemini returned empty response, using fallback")
            return create_fallback_prompt(user_prompt)
        
        # Validation: Ensure key constraints are present
        required_terms = ["no text", "no watermark", "high quality"]
        missing_terms = [term for term in required_terms if term.lower() not in refined_prompt.lower()]
        
        if missing_terms:
            logger.warning(f"Gemini output missing terms: {missing_terms}. Adding them.")
            refined_prompt += f", {', '.join(missing_terms)}, printable design"
        
        return refined_prompt
        
    except Exception as e:
        logger.error(f"Gemini API error: {str(e)}")
        logger.info("Falling back to rule-based prompt refinement")
        return create_fallback_prompt(user_prompt)

def create_fallback_prompt(user_prompt: str) -> str:
    """
    Fallback prompt refinement if Gemini API fails
    Uses simple rule-based enhancement
    
    Args:
        user_prompt: Original user input
        
    Returns:
        Enhanced prompt with quality constraints
    """
    
    # Basic enhancement template
    fallback = (
        f"A professional high-quality image of {user_prompt}, "
        f"clean white background, no text in image, no watermark, "
        f"studio lighting, sharp focus, printable design, "
        f"suitable for professional use"
    )
    
    return fallback

def test_gemini_connection():
    """
    Test function to verify Gemini API is working
    """
    try:
        test_prompt = "a simple flower"
        refined = refine_prompt_with_gemini(test_prompt)
        print(f"✓ Gemini API working")
        print(f"  Original: {test_prompt}")
        print(f"  Refined: {refined}")
        return True
    except Exception as e:
        print(f"✗ Gemini API error: {str(e)}")
        return False

if __name__ == "__main__":
    # Test the module
    print("Testing Gemini Prompt Refinement...")
    test_gemini_connection()
