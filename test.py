import cv2
import numpy as np

def remove_watermark(image_path, output_path):
    # Cargar la imagen
    image = cv2.imread(image_path)
    gray = cv2.cvtColor(image, cv2.COLOR_BGR2GRAY)
    
    # Detectar posibles áreas de la marca de agua
    _, mask = cv2.threshold(gray, 200, 255, cv2.THRESH_BINARY)
    
    # Refinar la máscara
    kernel = np.ones((3,3), np.uint8)
    mask = cv2.dilate(mask, kernel, iterations=1)
    mask = cv2.erode(mask, kernel, iterations=1)
    
    # Aplicar inpainting para eliminar la marca de agua
    result = cv2.inpaint(image, mask, inpaintRadius=3, flags=cv2.INPAINT_TELEA)
    
    # Guardar la imagen procesada
    cv2.imwrite(output_path, result)
    print(f"Imagen procesada guardada en: {output_path}")

# Uso
def main():
    input_image = "input_image.jpg"  # Reemplaza con tu imagen
    output_image = "output_image.jpg"
    remove_watermark(input_image, output_image)

if __name__ == "__main__":
    main()

