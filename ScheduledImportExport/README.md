# 📦 Scheduled Import/Export Success Email - Magento 2 Module

Módulo Magento 2 que estende a funcionalidade nativa de **Import/Export Agendado** (*System > Scheduled Import/Export*), permitindo o envio de **e-mails de sucesso** após a execução dos processos.

---

## 🚀 Sobre o módulo

Por padrão, o Magento 2 envia notificações por e-mail **apenas em caso de falha** nos processos de importação e exportação agendados.

Este módulo foi desenvolvido para complementar essa limitação, adicionando a possibilidade de envio de **e-mails de sucesso**, garantindo maior visibilidade e controle operacional.

---

## 🛠️ Tecnologias utilizadas

* **Magento 2**
* **PHP 8.3**
* **MySQL**
* **Cron Jobs (Magento Scheduler)**

---

## ⚙️ Funcionalidades

* 📧 Envio de e-mail em caso de **sucesso** na execução de importação/exportação
* 🧾 Suporte à geração de arquivos `.csv`
* 🔗 Inclusão de **link para download do arquivo gerado**
* 🎨 Configuração de **template de e-mail personalizado**
* 👥 Definição de **destinatários múltiplos**
* ⏱️ Integração com o sistema nativo de agendamento do Magento

---

## 🎯 Objetivo

Fornecer maior controle e transparência sobre os processos automatizados de importação e exportação, permitindo que equipes sejam notificadas sempre que uma operação for concluída com sucesso.

---

## 🧩 Integração com funcionalidade nativa

O módulo atua diretamente sobre:

```id="zv8g0k"
System > Scheduled Import/Export
```

Extendendo o comportamento padrão sem alterar o fluxo original do Magento.

---

## 🔧 Instalação

1. Copie o módulo para:

```id="p3r4j8"
app/code/ScheduledImportExport
```

2. Execute os comandos:

```bash id="2v2q8c"
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento cache:flush
```

---

## ⚙️ Configuração

Após a instalação:

1. Acesse o Admin do Magento
2. Navegue até:

   * **System > Scheduled Import/Export**
3. Configure:

   * Template de e-mail
   * <img width="1402" height="658" alt="image" src="https://github.com/user-attachments/assets/ebde1416-bfd8-4e36-9e93-7492167b800c" />
   * Lista de destinatários
4. Execute ou aguarde o processamento via cron
   * Email enviado
   * <img width="790" height="465" alt="image" src="https://github.com/user-attachments/assets/22319263-6584-48a3-829d-539e9c025d2d" />


---

## 🔐 Funcionamento

* Após a execução de um processo de **Import** ou **Export**
* Quando o arquivo `.csv` é gerado com sucesso:

  * Um e-mail é enviado automaticamente
  * Contendo:

    * Informação de sucesso
    * Link para download do arquivo gerado

---

## 📌 Benefícios

* ✔️ Monitoramento proativo de processos automatizados
* ✔️ Redução de verificações manuais
* ✔️ Maior confiabilidade operacional
* ✔️ Melhor comunicação entre equipes

---

## 👨‍💻 Autor

Desenvolvido por **Bruno Gomes**
🔗 https://github.com/bgomesweb

---

## 📄 Licença

Este projeto é de uso privado e não possui licença aberta no momento.
