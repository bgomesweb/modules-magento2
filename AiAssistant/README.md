# Bgomesweb_AiAssistant — Especificação Funcional

## 1. Resumo Funcional do Módulo

- **Nome Técnico do Módulo:** `Bgomesweb_AiAssistant`
- **Área de Atuação:** Storefront (Área do Cliente)
- **Objetivo da Funcionalidade:** Adiciona um assistente virtual inteligente baseado em IA (Google Gemini) ao storefront do Magento 2. O widget permite ao cliente conversar naturalmente para buscar produtos no catálogo, comparar especificações, receber sugestões contextualizadas e obter suporte — tudo dentro de um painel de chat flutuante. O módulo integra-se ao provedor `Bgomesweb_GeminiProvider` para geração de respostas e utilza um sistema de ferramentas (function calling) para buscas no catálogo, comparações e ações de navegação.
- **Permissões de Acesso (ACL):** `Bgomesweb_AiAssistant::config` (Lojas > Configurações > Bgomesweb > Ai Assistant). Requer permissão de administração para acessar as configurações no painel administrativo. O storefront não requer ACL adicional.
- **Dependências entre Módulos:**
  - `Bgomesweb_GeminiProvider` (obrigatório) — provedor de IA que expõe a interface `GeminiClientInterface`.
  - `Magento_Cms` — utilizado para carregar blocos CMS como base de conhecimento dos tópicos.
  - `Magento_Catalog` / `Magento_CatalogSearch` — busca e renderização de produtos.
  - `Magento_Checkout` — helpers de carrinho para adicionar produtos e gerar URLs de "Comprar".

## 2. Configurações do Sistema (System Configuration)

- **Caminho no Menu:** Lojas > Configurações > Bgomesweb > Ai Assistant
- **Visibilidade:** Default, Website e Store View (configurável por escopo)

### Aba: General

| Campo | Tipo | Valor Padrão | Descrição |
| :--- | :--- | :--- | :--- |
| `Enable AI Assistant` | Select (Sim/Não) | `Sim` | Habilita ou desabilita o widget do Assistente de IA por Store View. Quando desabilitado, o widget não é renderizado e os endpoints de chat retornam erro. |
| `Generation Prompt` | Textarea | *(prompt detalhado em português para o assistente ASUS Brasil)* | Prompt de sistema enviado ao provedor Gemini ao gerar respostas. Define identidade, escopo, diretrizes de comportamento, uso de ferramentas e guardrails. Configurável por Store View. |
| `Display Pages` | Multiselect | `all` (Todas as páginas) | Define em quais páginas o widget é exibido. Opções: Todas as páginas, Página inicial, Páginas CMS, Páginas de categoria, Páginas de produto, Resultados de busca, Carrinho de compras, Finalizar compra, Painel do cliente, Meus pedidos, Fale conosco. |

### Aba: Topics

| Campo | Tipo | Descrição |
| :--- | :--- | :--- |
| `Topics Configuration` | Tabela serializada (Topic Name, Suggested Query, CMS Block) | Permite configurar tópicos sugeridos que aparecem na tela de boas-vindas do chat. Cada tópico possui um nome exibido, uma consulta sugerida enviada ao assistente e opcionalmente um bloco CMS cujo conteúdo é injetado como base de conhecimento na conversa. |

**Valores padrão semeados na instalação:**

| Tópico | Consulta Sugerida |
| :--- | :--- |
| Notebooks on sale | Notebooks in promotions |
| Order help | I need help with my order |
| Technical support | I need technical support |
| Payment methods | What are the payment methods? |
| Track delivery | I want to track my delivery |

## 3. Fluxos de Tela e Ações do Usuário

### Tela: Widget de Chat Flutuante (Storefront)

- **Localização:** Injetado automaticamente em todas as páginas (ou nas páginas selecionadas) via layout XML `before.body.end`.
- **Ações Disponíveis:**
  - **Abrir Chat (FAB):** Botão flutuante (ícone de chat) que expande o painel de conversa. Ao clicar, carrega o histórico da sessão atual via `ai-assistant/chat/load`.
  - **Enviar Mensagem:** Campo de texto no rodapé do chat. O cliente digita uma pergunta e clica no botão "Enviar" ou pressiona Enter. A mensagem é enviada via POST para `ai-assistant/chat/send`.
  - **Tópicos Sugeridos (Tela de Boas-vindas):** Quando não há mensagens na conversa, são exibidos botões de tópicos pré-configurados (ex: "Notebooks em promoção", "Ajuda com pedido"). Clicar em um tópico envia a consulta sugerida associada ao assistente.
  - **Botões de Resposta Rápida (Suggested Replies):** Após uma resposta do assistente, podem aparecer botões contextualizados gerados pela ferramenta `suggest_replies`. Clicar em um envia o texto associado como nova mensagem.
  - **Comprar (Add to Cart):** Botão "Comprar" nos cards de produto que adiciona o produto ao carrinho via AJAX, exibindo mensagem de status de sucesso ou erro.
  - **Nova Conversa:** Botão no cabeçalho que limpa o histórico da sessão via POST para `ai-assistant/chat/clear`.
  - **Expandir/Tela Cheia:** Alterna o chat entre modo janela e tela cheia. O estado é persistido no `localStorage`.
  - **Fechar:** Minimiza o chat, ocultando o painel e exibindo apenas o FAB.

### Tela: Cards de Produto no Chat

- **Exibição:** Produtos retornados pelo assistente são exibidos como cards visuais com imagem, nome, preço e botão "Comprar".
- **Galeria de Imagens:** Cards com múltiplas imagens utilizam o componente Swiper para navegação em carrossel (autoplay, paginação, navegação).
- **Tabela de Comparação:** Quando o assistente retorna um grupo de produtos, uma tabela de comparação é exibida abaixo dos cards, alinhando especificações técnicas (Processador, Memória RAM, Armazenamento, Tela, etc.) lado a lado.
- **Cards de Grupo:** Produtos são agrupados por consulta de busca. Cada grupo possui um título (a consulta) e seus respectivos cards.

### Tela: Configurações no Admin

- **Caminho no Menu:** Lojas > Configurações > Bgomesweb > Ai Assistant
- **Ações Disponíveis:**
  - **Habilitar/Desabilitar:** Alterna o estado do assistente.
  - **Editar Prompt de Geração:** Edita o system prompt enviado ao Gemini.
  - **Selecionar Páginas:** Define onde o widget é exibido.
  - **Gerenciar Tópicos:** Adiciona, edita ou remove tópicos sugeridos. Para cada tópico: nome, consulta sugerida e bloco CMS associado (selecionado via dropdown dinâmico).

## 4. Comportamentos Automáticos e Gatilhos (Observers / Plugins)

- **Exibição Condicional do Widget:** O widget só é renderizado se o módulo estiver habilitado E a página atual estiver na lista de `display_pages`. A verificação é feita pelo método `Config::isWidgetActive()` que compara a `full_action_name` da página com as páginas permitidas.
- **Loop de Execução de Ferramentas (Function Calling):** Quando o cliente envia uma mensagem, o sistema entra em um loop iterativo (máximo de iterações) onde: (1) envia a mensagem ao Gemini com o histórico e ferramentas disponíveis; (2) se o Gemini retorna uma chamada de função backend, executa a ferramenta e retorna o resultado ao modelo; (3) repete até o modelo gerar uma resposta em texto ou uma ferramenta frontend.
- **Injeção de Base de Conhecimento por Tópico:** Quando um tópico ativo possui um bloco CMS associado, o conteúdo do bloco é extraído (texto puro, sem HTML/scripts) e injetado no prompt de sistema como "BASE DE CONHECIMENTO DA LOJA".
- **Histórico de Conversa (Session):** As mensagens são persistidas na sessão do cliente (máximo de 12 mensagens). O histórico é truncado automaticamente quando excede o limite.
- **Deduplicação de Chamadas de Ferramentas:** O `ToolExecutionLoop` previne execução duplicada da mesma ferramenta com os mesmos parâmetros dentro de uma mesma iteração.
- **Prevenção de Injeção de Scripts:** O `TextSanitizer` remove cercas de markdown (```` ``` ````) e tenta decodificar JSON da resposta do modelo para extrair apenas o campo `message`, prevenindo injeção de conteúdo não desejado.
- **Truncamento de Mensagens:** Mensagens do cliente com mais de 2000 caracteres são truncadas automaticamente e um aviso é retornado.

## 5. Mensagens do Sistema e Tratamento de Erros

| Evento / Ação | Mensagem Exibida no Magento | Causas e Orientação ao Usuário |
| :--- | :--- | :--- |
| Assistente desabilitado na loja | *"Assistente virtual desativado nesta loja."* | O administrador precisa habilitar o assistente em Lojas > Configurações > Bgomesweb > Ai Assistant. |
| Payload inválido na requisição | *"Invalid request payload."* | A requisição JSON enviada está malformada. Atualizar a página e tentar novamente. |
| Chave CSRF inválida | *"Invalid form key. Please refresh the page."* | A sessão expirou ou o form_key não corresponde. Atualizar a página. |
| Mensagem em branco | *"Mensagem em branco."* | O usuário tentou enviar uma mensagem vazia. Digitar algo antes de enviar. |
| Erro ao processar mensagem | *"Desculpe, ocorreu um erro ao processar sua mensagem. Por favor, tente novamente."* | Erro interno no processamento (timeout do Gemini, erro de busca, etc.). Verificar logs do sistema para detalhes. |
| Mensagem truncada | *"Message was truncated to 2000 characters."* (warning) | A mensagem do usuário excedeu 2000 caracteres e foi cortada. Reformular a mensagem. |
| Nova conversa iniciada | *"New conversation started."* | Sucesso ao limpar a conversa anterior. |
| Produto adicionado ao carrinho | *"O produto %1 foi adicionado com sucesso ao carrinho."* | Confirmação de que o item foi adicionado. |
| Falha ao adicionar ao carrinho | *"Não foi possível adicionar o produto ao carrinho. Tente novamente."* | O produto pode estar fora de estoque, indisponível ou houve erro de rede. |
| Resposta sem conteúdo do assistente | *"Desculpe, não consegui interpretar sua pergunta. Pode reformulá-la?"* | O modelo de IA não conseguiu gerar uma resposta compreensível. Reformular a pergunta. |
| Resposta sem produtos encontrados | *"Não encontrei produtos compatíveis com esses critérios no catálogo. Posso tentar outra opção?"* | A busca no catálogo não retornou resultados. Tentar com outros termos ou filtros. |
| Ferramenta não encontrada | *"Ferramenta não encontrada."* | A IA tentou chamar uma ferramenta inexistente. Erro interno, verificar configuração das tools. |
| Ferramenta sem executor | *"A ferramenta %1 não possui executor configurado."* | Handler da ferramenta não foi injetado corretamente. Verificar `di.xml`. |
| Ação indisponível no momento | *"Não consegui realizar essa ação no momento."* | Erro transiente na execução de uma ferramenta backend. Tentar novamente. |
| Erro genérico de comunicação | *"Desculpe, algo deu errado. Tente novamente em instantes."* | Falha na comunicação com a API do Gemini ou timeout de rede. |
| Resposta não pôde ser gerada | *"Não consegui gerar uma resposta. Tente reformular sua pergunta."* | O modelo retornou vazio sem ferramentas. Reformular a pergunta. |
