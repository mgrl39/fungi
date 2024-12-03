# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG
# FINAL SIN IMG

import requests
from bs4 import BeautifulSoup
import json
import os
import re

# Debugging colors
class bcolors:
    OKCYAN = '\033[96m'
    BOLD = '\033[1m'
    ENDC = '\033[0m'
    FAIL = '\033[91m'

def print_text(name, text):
    print(f"{bcolors.OKCYAN}{bcolors.BOLD}{name}:{bcolors.ENDC} {text if text else f'{bcolors.FAIL}NULL{bcolors.ENDC}'}")

def format_name(name):
    return name.strip().lower().replace(' ', '-')

def get_text_or_none(soup_element):
    return soup_element.text.strip() if soup_element else None

def scrape_fungi_details(url):
    try:
        response = requests.get(url)
        if response.status_code != 200:
            print(f"{bcolors.FAIL}Error accessing {url}{bcolors.ENDC}")
            return None

        soup = BeautifulSoup(response.text, 'html.parser')

        # Title and Author
        title = get_text_or_none(soup.select_one('h1.itemTitle'))
        author = get_text_or_none(soup.select_one('span.itemAuthor'))

        # Additional Information (including "Common name" and "Synonym")
        additional_info = {}
        additional_info_section = soup.find('div', class_='itemExtraFieldsFungi')
        if additional_info_section:
            for li in additional_info_section.find_all('li', class_='caracteristica'):
                label = li.find('span', class_='itemExtraFieldsLabel')
                value = li.find('span', class_='itemExtraFieldsValue')
                if label and value:
                    key = label.text.strip().strip(':')
                    val = value.text.strip()
                    additional_info[key] = val

        common_name = additional_info.get("Nombre común")
        synonym = additional_info.get("Sinónimo")

        # Taxonomy (excluding "Common name" and "Synonym")
        taxonomy_section = soup.find('div', class_='taxonomia')
        taxonomy = {}
        if taxonomy_section:
            for li in taxonomy_section.find_all('li', class_='caracteristica'):
                label = li.find('span', class_='itemExtraFieldsLabel')
                value = li.find('span', class_='itemExtraFieldsValue')
                if label and value:
                    key = label.text.strip().strip(':')
                    val = value.text.strip()
                    if key not in ["Nombre común", "Sinónimo"]:
                        taxonomy[key] = val

        # Edibility
        edibility = None
        edibility_img = soup.select_one('div.itemToolbarFungi img')
        if edibility_img:
            match = re.search(r'/images/comestibilidad/(.*?).png', edibility_img['src'])
            if match:
                edibility = match.group(1)

        # Characteristics, Habitat, and Observations
        characteristics = {}
        habitat, observations = None, None

        full_text = soup.select_one('div.itemFullText')
        if full_text:
            h3_headers = full_text.find_all('h3')
            paragraphs = full_text.find_all('p')
            current_section = None

            for header in h3_headers:
                if "Características" in header.text:
                    current_section = "characteristics"
                elif "Hábitat" in header.text:
                    current_section = "habitat"
                elif "Observaciones" in header.text:
                    current_section = "observations"

                if current_section == "characteristics":
                    characteristics["cap"] = paragraphs[0].text.strip()
                    characteristics["hymenium"] = paragraphs[1].text.strip()
                    characteristics["stipe"] = paragraphs[2].text.strip()
                    characteristics["flesh"] = paragraphs[3].text.strip()
                elif current_section == "habitat":
                    habitat = paragraphs[4].text.strip()
                elif current_section == "observations":
                    observations = paragraphs[5].text.strip()

        # Structured data
        data = {
            "name": url.split('/')[-1].replace('.html', ''),
            "title": title,
            "author": author,
            "additional_info": {
                "common_name": common_name,
                "synonym": synonym,
            },
            "taxonomy": taxonomy,
            "edibility": edibility,
            "characteristics": characteristics,
            "habitat": habitat,
            "observations": observations,
        }

        return data
    except Exception as e:
        print(f"Error processing {url}: {e}")
        return None

def main():
    with open('names.txt', 'r', encoding='utf-8') as file:
        names = [line.strip() for line in file.readlines()]

    base_url = "https://www.fungipedia.org/hongos/"
    os.makedirs('output_jsons', exist_ok=True)

    for name in names:
        print(f"{bcolors.OKCYAN}<===================================================>{bcolors.ENDC}")
        formatted_name = format_name(name)
        url = f"{base_url}{formatted_name}.html"
        print_text("URL", url)

        fungi_details = scrape_fungi_details(url)
        if fungi_details:
            json_filename = f'output_jsons/{formatted_name}.json'
            with open(json_filename, 'w', encoding='utf-8') as json_file:
                json.dump(fungi_details, json_file, ensure_ascii=False, indent=4)
            print(f"Saved to {json_filename}")

    print(f"{bcolors.OKCYAN}{bcolors.BOLD}Scraping completed. All JSON files generated for individual fungi.{bcolors.ENDC}")

if __name__ == "__main__":
    main()

