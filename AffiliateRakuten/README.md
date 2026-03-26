# 🔗 Affiliate Rakuten - Magento 2 Module

Módulo Magento 2 responsável pela integração com o programa de afiliados da **Rakuten**, permitindo o rastreamento e envio de dados de pedidos para a plataforma.

---

## 🚀 Sobre o módulo

Este módulo foi desenvolvido para integrar o Magento 2 com a **Rakuten Affiliate Network**, possibilitando o envio de informações de pedidos para fins de rastreamento de afiliados.

A implementação garante que conversões sejam corretamente registradas, contribuindo para estratégias de marketing e parcerias.

---

## 🛠️ Tecnologias utilizadas

* **Magento 2**
* **PHP 8.2**
* **REST / HTTP Requests**
* **MySQL**

---

## ⚙️ Funcionalidades

* 🔗 Integração com a plataforma Rakuten
* 🛒 Rastreamento de pedidos realizados
* 📊 Envio de dados de conversão
* 🎯 Suporte a estratégias de marketing de afiliados
* ⚡ Execução otimizada sem impacto na performance do checkout

---

## 🧩 Estrutura do módulo

O módulo segue os padrões do Magento 2:

```id="r8k5sk"
AffiliateRakuten/
├── Controller/
├── Observer/
├── Service/
├── etc/
```

---

## 🔧 Instalação

1. Copie o módulo para:

```id="caz3ds"
app/code/AffiliateRakuten
```

2. Execute os comandos:

```bash id="6qz7t9"
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## ⚙️ Configuração

Após a instalação:

1. Acesse o Admin do Magento
2. Configure os parâmetros da Rakuten (quando aplicável)
3. Valide o envio dos dados de conversão

---

## 🎯 Objetivo

Permitir o rastreamento confiável de conversões originadas por afiliados, garantindo:

* Precisão nos dados de marketing
* Integração com plataformas externas
* Escalabilidade da solução

---

## 🔐 Integração

O módulo realiza comunicação com a Rakuten para:

* Envio de dados de pedidos
* Registro de conversões
* Suporte ao tracking de afiliados

---

## 👨‍💻 Autor

Desenvolvido por **Bruno Gomes**
🔗 https://github.com/bgomesweb

---

## 📄 Licença

Este projeto é de uso privado e não possui licença aberta no momento.
