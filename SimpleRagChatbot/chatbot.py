from collections import defaultdict
from pprint import pprint
from langchain_community.document_loaders import DirectoryLoader
from langchain_core.documents import Document
from langchain_text_splitters import Language, RecursiveCharacterTextSplitter
from langchain_unstructured import UnstructuredLoader
from langchain_openai import OpenAIEmbeddings, ChatOpenAI
from langchain_community.vectorstores import FAISS
from langchain_community.vectorstores.utils import DistanceStrategy
from langchain_core.prompts import ChatPromptTemplate
from langchain_core.output_parsers import StrOutputParser
from langchain_core.runnables import RunnablePassthrough
from dotenv import load_dotenv
import os

from langchain_experimental.text_splitter import SemanticChunker


os.environ["KMP_DUPLICATE_LIB_OK"] = "TRUE"

load_dotenv()

loader = DirectoryLoader(
    path="./Papers/school",
    glob="**/*.txt",
    loader_cls=UnstructuredLoader,
    loader_kwargs={"languages": ["nld"]},
    show_progress=True,
    use_multithreading=True,
)

docs = loader.load()


# UnstructuredLoader geeft één Document per element (titel, alinea, footer, ...).
# Voeg ze per PDF samen tot één Markdown-tekst, zodat de splitter iets te splitsen heeft
# en de MARKDOWN_SEPARATORS op koppen kunnen matchen.
# parts_per_source = defaultdict(list)
# for element in elements:
#     category = element.metadata.get("category")
#     if category in ("Footer", "Header", "PageNumber"):
#         continue
#     text = element.page_content
#     if category == "Title":
#         text = f"# {text}"
#     parts_per_source[element.metadata["source"]].append(text)

# docs = [
#     Document(page_content="\n\n".join(parts), metadata={"source": source})
#     for source, parts in parts_per_source.items()
# ]

textSplitter = RecursiveCharacterTextSplitter.from_language(
    Language.MARKDOWN,
    chunk_size=1200,
    chunk_overlap=200,
    add_start_index=True,
    strip_whitespace=True,
)
embeddings= OpenAIEmbeddings(
    model="text-embedding-3-large"
)

# breakpoint_threshold_amount means the threshold for determining where to split the text based on semantic similarity.
# if the similarity between two chunks is below this threshold, a split will occur.
# textSplitter = SemanticChunker(
#     embeddings= embeddings,
#     breakpoint_threshold_amount=85
# )

# chunk the documents into smaller pieces using the text splitter
splits = textSplitter.split_documents(docs)

#use the embeddings to create a FAISS vector store


print("Number of document splits:", len(splits))
for i, doc in enumerate(splits):
    print(f"\n--- Chunk {i} | {doc.metadata.get('source')} | start_index={doc.metadata.get('start_index')} | {len(doc.page_content)} tekens ---")
    print(doc.page_content)


# create the FAISS vector store from the document splits
# vectorstore = FAISS.from_documents(
#     documents=splits,
#     embedding=embeddings,
#     # FAISS gebruikt een L2-index; reken de gekwadrateerde L2-afstand om naar cosine-similariteit (0..1)
#     relevance_score_fn=lambda distance: 1.0 - distance / 2,
# )


# # create a retriever from the FAISS vector store
# # similarity_score_threshold means it will only return documents with a similarity score above the specified threshold
# # k is the number of top documents to return here is 5
# # score_threshold is the minimum similarity score a document must have to be returned   
# retriver = vectorstore.as_retriever(
#     search_type="similarity_score_threshold",
#     search_kwargs ={"k": 5, "score_threshold": 0.2}
# )

# template = (
#     "You are a helpful assistant. Use the following context to answer the question.\n\n"
#     "RULES:\n"
#     "1. Answer based on the provided context.\n"
#     "2. If the context does not contain the answer, respond with 'I don't know.'\n"
#     "3. Do not use outside knowledge, guessing, or web information.\n"
#     "4. if applicable, cite the source of the information as (source:page) using. metadata.\n\n"
#     "Context: \n{context}\n\n"
#     "Question: {question}\n"
# )

# prompt = ChatPromptTemplate.from_template(template)

# llm = ChatOpenAI(
#     model = "gpt-5-mini",
#     temperature=0,
# )

# # create the RAG (Retrieval-Augmented Generation) chain using the retriever, prompt, LLM, and output parser
# rag_chain = (
#     {"context": retriver, "question": RunnablePassthrough()}
#     | prompt 
#     | llm 
#     | StrOutputParser()
# )

# question = input("Enter your question: ")

# answer = rag_chain.invoke(question)

# print("Answer:", answer)
