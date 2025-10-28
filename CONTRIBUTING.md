# 🤝 Contribuindo para R4PHP

Obrigado por querer contribuir com o **R4PHP**!  
Este projeto faz parte do ecossistema **Resilience4u**, voltado para bibliotecas de resiliência multi‑linguagem (Go, PHP, JS).

Queremos que contribuir seja uma experiência simples, aberta e produtiva — então siga este guia para ajudar a manter o padrão de qualidade do projeto. 💪

---

## Como contribuir

1. **Fork** o repositório
2. Crie uma nova branch:
   ```bash
   git checkout -b feat/nome-da-feature
   ```
3. Faça suas alterações e **adicione testes**
4. Execute os testes antes de enviar:
   ```bash
   composer test
   ```
5. Envie um Pull Request com uma descrição clara da mudança

---

## Diretrizes gerais

- Utilize **PSR‑12** como padrão de formatação de código.
- Use **nomes descritivos** para variáveis, métodos e classes.
- Adicione comentários apenas onde realmente ajudam na compreensão.
- Todas as novas funcionalidades devem incluir **testes automatizados**.
- Mantenha a compatibilidade com **PHP ≥ 8.1**.
- Evite dependências desnecessárias — o projeto deve permanecer **leve e modular**.

---

##  Testes

O projeto usa **PHPUnit**.  
Para rodar os testes:

```bash
composer install
composer test
```

Se adicionar uma nova política ou integração, inclua testes cobrindo:
- Comportamento esperado e de erro
- Integração com outras políticas (Factory)
- Performance e limites de execução (quando aplicável)

---

## Commits e Versionamento

- Siga o padrão **Conventional Commits**:  
  `feat:`, `fix:`, `chore:`, `test:`, `docs:`, `refactor:`, `perf:`  
- O versionamento segue **SemVer 2.0**:
  - **MAJOR** → mudanças incompatíveis
  - **MINOR** → novas features compatíveis
  - **PATCH** → correções e melhorias internas

---

## Estrutura recomendada de PR

Cada Pull Request deve incluir:
- [x] Descrição clara da alteração
- [x] Motivação (por que essa mudança é necessária)
- [x] Referência a issue (se aplicável)
- [x] Testes cobrindo o novo comportamento
- [x] Atualização de documentação (README, exemplos)

---

## 🛡️ Código de Conduta

Todos os colaboradores devem seguir o [Código de Conduta](CODE_OF_CONDUCT.md).  
Mantenha interações respeitosas, inclusivas e construtivas.

---

##  Roadmap de Contribuição

Algumas ideias de onde você pode ajudar:
- [ ] Implementar nova política (**Fallback**, **Cache**, etc.)
- [ ] Adicionar métricas e suporte a **OpenTelemetry**
- [ ] Criar exemplos de integração (Laravel, Symfony)
- [ ] Melhorar documentação e tutoriais
- [ ] Traduzir docs (EN/PT‑BR)
- [ ] Criar benchmark comparativo com Resilience4j

---

##  Contato

Dúvidas, sugestões ou bugs?  
Abra uma **issue** no GitHub ou entre em contato pela organização **[Resilience4u](https://github.com/resilience4u)**.

---

Feito com ❤️ por contribuidores open‑source.
