import requests
import json
import os

# Debugging colors
class bcolors:
    OKCYAN = '\033[96m'
    BOLD = '\033[1m'
    ENDC = '\033[0m'
    FAIL = '\033[91m'

def print_text(name, text):
    print(f"{bcolors.OKCYAN}{bcolors.BOLD}{name}:{bcolors.ENDC} {text if text else f'{bcolors.FAIL}NULL{bcolors.ENDC}'}")

def format_name(name):
    return name.strip().lower().replace(' ', '_')

def scrape_fungi_images(base_url, fungus_name):
    images = []
    counter = 0
    consecutive_not_found = 0

    while consecutive_not_found < 2:
        # Generate the image URL
        image_url = f"{base_url}{fungus_name}/{fungus_name}{'' if counter == 0 else counter}.jpg"
        response = requests.get(image_url)

        if response.status_code == 200:
            print(f"{bcolors.OKCYAN}Found image: {image_url}{bcolors.ENDC}")
            images.append(image_url)
            consecutive_not_found = 0  # Reset not-found counter
        else:
            print(f"{bcolors.FAIL}Image not found: {image_url}{bcolors.ENDC}")
            consecutive_not_found += 1

        counter += 1

    return images

def main():
    # Read fungus names from names.txt
    with open('names.txt', 'r', encoding='utf-8') as file:
        names = [line.strip() for line in file.readlines()]

    base_url = "https://www.fungipedia.org/images/galerias/"
    os.makedirs('img_jsons', exist_ok=True)

    for name in names:
        print(f"{bcolors.OKCYAN}<===================================================>{bcolors.ENDC}")
        formatted_name = format_name(name)
        print_text("Processing fungus", formatted_name)

        # Scrape images for the fungus
        images = scrape_fungi_images(base_url, formatted_name)

        # Save the data as JSON
        json_filename = f'img_jsons/img_{formatted_name}.json'
        data = {"fungus_name": name, "images": images}

        with open(json_filename, 'w', encoding='utf-8') as json_file:
            json.dump(data, json_file, ensure_ascii=False, indent=4)

        print(f"{bcolors.OKCYAN}Saved image JSON to {json_filename}{bcolors.ENDC}")

    print(f"{bcolors.OKCYAN}{bcolors.BOLD}Image scraping completed. All JSON files saved in 'img_jsons'.{bcolors.ENDC}")

if __name__ == "__main__":
    main()

