# 💳 PagaLeve API - Magento 2 Module

Módulo Magento 2 responsável pela integração com a API da **PagaLeve**, permitindo a consulta de parcelas de pedidos e sua exibição no sistema.

---

## 🚀 Sobre o módulo

Este módulo foi desenvolvido para integrar o Magento 2 com a API da PagaLeve, possibilitando:

* Consulta das parcelas de pedidos diretamente via API
* Exibição das informações no **Admin do Magento**
* Disponibilização dos dados no **frontend (Minha Conta do cliente)**

A solução foi construída com foco em organização, escalabilidade e compatibilidade com versões modernas do PHP.

---

## 🛠️ Tecnologias utilizadas

* **Magento 2**
* **PHP 8.5**
* **REST API (PagaLeve)**
* **MySQL**

---

## ⚙️ Funcionalidades

* 🔎 Consulta de parcelas via API da PagaLeve
* 📦 Integração com pedidos do Magento
* 🧾 Exibição das parcelas no painel administrativo
* 👤 Exibição das parcelas na área do cliente (Minha Conta)
* 🔗 Comunicação com API externa via requisições HTTP

---

## 🧩 Estrutura do módulo

O módulo segue o padrão de desenvolvimento do Magento 2:

```
PagaLeveApi/
├── Api/
├── Block/
├── Cron/
├── etc/
├── Helper/
├── i18n/
├── Model/
├── Plugin/
├── Service/
├── View/
```

---

## 🔧 Instalação

1. Copie o módulo para:

```
app/code/PagaLeveApi/PagaLeveApi
```

2. Execute os comandos:

```bash id="qv9v1m"
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## ⚙️ Configuração

Após a instalação:

1. Acesse o painel administrativo do Magento
2. Configure as credenciais da API da PagaLeve
3. Verifique a integração com pedidos

---

## 🎯 Objetivo

Facilitar a visualização e gestão de pagamentos parcelados via PagaLeve, tanto para:

* Administradores (backoffice)
* Clientes (transparência no frontend)

---

## 🔐 Integração com API

O módulo realiza chamadas para a API da PagaLeve para:

* Obter dados de parcelamento
* Relacionar informações com pedidos existentes no Magento

---

## 👨‍💻 Autor

Desenvolvido por **Bruno Gomes**
🔗 https://github.com/bgomesweb

---

## 📄 Licença

Este projeto é de uso privado e não possui licença aberta no momento.
