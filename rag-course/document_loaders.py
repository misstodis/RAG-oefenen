import os
import tempfile
from pathlib import Path
import warnings
warnings.filterwarnings("ignore", category=DeprecationWarning, message=".*langchain-community.*")

from langchain_community.document_loaders import TextLoader
from dotenv import load_dotenv

load_dotenv()

def load_text_file():
    with tempfile.NamedTemporaryFile(delete=False, mode="w", suffix=".txt") as temp_file:
        temp_file.write("Sample text content ducky, du ma may con cho")
        temp_file_path = Path(temp_file.name)

    try:
        loader = TextLoader(str(temp_file_path))
        documents = loader.load()

        print(f"loaded {len(documents)} documents")
        print(f"Content preview: {documents[0].page_content[:100]}...")
        print(f"Full content: {documents[0].metadata}")

        # for doc in documents:
        #     print("Document content:")
        #     print(doc)
        #     print("--------------------")
        #     print(doc.page_content)
    finally:
        os.remove(temp_file_path)


if __name__ == "__main__":
    load_text_file()

