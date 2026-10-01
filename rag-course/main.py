from dotenv import load_dotenv
load_dotenv()

from langchain_core import __version__ as core_version
from importlib.metadata import version

lg_version = version("langgraph")
from langchain_openai import ChatOpenAI
from langchain_anthropic import ChatAnthropic


print(f"LangChain Core Version: {core_version}")
print(f"LangGraph Version: {lg_version}")

def main():
    llm = ChatOpenAI(model_name="gpt-4o-mini", temperature=0)
    response = llm.invoke("Say 'setup complete' hello ducky")
    print(f"Response from ChatOpenAI: {response}")
    

    # llm_anthropic = ChatAnthropic(model_name="claude-sonnet-4-5-20250929", temperature=0)
    # response_anthropic = llm_anthropic.invoke("Say 'setup complete' hello ducky")
    # print(f"Response from ChatAnthropic: {response_anthropic}")


if __name__ == "__main__":
    main()
