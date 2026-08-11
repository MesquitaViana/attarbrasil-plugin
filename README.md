# Attar Brasil Storefront

Plugin customizado para **WordPress + WooCommerce** que transforma a camada de storefront da loja em uma experiência de e-commerce mais completa, com vitrines por shortcode, catálogo filtrável, Product Detail Page (PDP) própria, avaliações com fotos, produtos relacionados, “compre junto” e recursos de SEO para páginas de catálogo.

O projeto nasceu para atender a operação da **Attar Brasil**, e está sendo disponibilizado como open source para servir como referência, base de estudo e ponto de partida para implementações personalizadas em lojas WooCommerce.

> **Versão atual:** 2.4.2

## Principais recursos

* Vitrines de produtos renderizadas via shortcode.
* Abas de **Mais vendidos**, **Lançamentos**, **Ofertas**, **Novidades**, **Destaques** e outros tipos de consulta.
* Vitrine separada por **Masculino / Feminino**.
* Catálogo responsivo com filtros e ordenação.
* Filtros por preço, marca, categoria, família olfativa, volume, concentração, gênero, estoque e oferta.
* Carregamento progressivo de produtos com fallback navegável.
* Cards de produto customizados para WooCommerce.
* Página individual de produto completa via `[attar_produto]`.
* Galeria responsiva com miniaturas, navegação e suporte a imagens de variações.
* Exibição dinâmica de preço, desconto, Pix e parcelamento.
* Bloco de progresso para frete grátis.
* Sistema de **Compre Junto** com até três combinações.
* Suporte a produtos simples e variáveis no Compre Junto.
* Seleção automática quando o produto complementar possui apenas uma variação comprável.
* Produtos relacionados.
* Avaliações nativas do WooCommerce com envio de até cinco fotos.
* Moderação de avaliações pelo painel do plugin.
* ALT automático para imagens enviadas em avaliações.
* Conteúdo editorial acima e abaixo da dobra em categorias.
* Painel administrativo para localizar shortcodes usados em páginas e templates.
* Configuração centralizada dos links das vitrines.
* Associação de SVGs às famílias olfativas.
* Regras de SEO para URLs geradas pelos filtros.
* Integração com Yoast SEO quando disponível.
* Compatibilidade opcional com wishlist e simuladores de frete baseados em shortcode.

## Requisitos

| Requisito                         | Versão          |
| --------------------------------- | --------------- |
| WordPress                         | 6.4 ou superior |
| PHP                               | 7.4 ou superior |
| WooCommerce                       | 8.0 ou superior |
| WordPress testado no pacote atual | até 6.8         |

O Elementor **não é obrigatório**, já que os componentes são disponibilizados por shortcode, mas o plugin foi desenvolvido pensando no uso dentro de páginas e templates do Elementor.

## Instalação

1. Baixe ou clone este repositório.
2. Copie a pasta `attar-brasil-storefront` para:

```text
/wp-content/plugins/
```

3. Acesse **WordPress > Plugins**.
4. Ative **Attar Brasil Storefront**.
5. Certifique-se de que o WooCommerce está instalado e ativo.
6. Use os shortcodes em páginas, widgets, blocos ou templates do Elementor.

## Estrutura do projeto

```text
attar-brasil-storefront/
├── assets/
│   ├── css/
│   │   └── storefront.css
│   └── js/
│       ├── admin.js
│       └── storefront.js
├── includes/
│   ├── class-abs-admin.php
│   ├── class-abs-content.php
│   ├── class-abs-pdp.php
│   ├── class-abs-plugin.php
│   ├── class-abs-product-card.php
│   ├── class-abs-query.php
│   ├── class-abs-reviews.php
│   └── class-abs-seo.php
├── templates/
│   ├── catalog.php
│   ├── product-card.php
│   └── product.php
├── attar-brasil-storefront.php
├── readme.txt
└── uninstall.php
```

## Shortcodes

### Grade simples de produtos

```text
[attar_produtos tipo="mais_vendidos" limite="8" colunas="4"]
```

Parâmetros disponíveis:

* `tipo`
* `limite`
* `colunas`
* `categoria`
* `marca`
* `ids`
* `excluir`
* `estoque`

Exemplo:

```text
[attar_produtos tipo="ofertas" limite="8" colunas="4" categoria="perfumes-arabes" estoque="sim"]
```

### Vitrine com duas abas

```text
[attar_vitrine_produtos titulo="Explore nossos perfumes" aba_1="mais_vendidos" aba_2="lancamentos" limite="8" colunas="4"]
```

Exemplo completo:

```text
[attar_vitrine_produtos titulo="Explore nossos perfumes" aba_1="mais_vendidos" aba_2="ofertas" rotulo_aba_1="Mais vendidos" rotulo_aba_2="Ofertas" limite="8" colunas="4" categoria="" marca="" estoque="sim" ver_todos_url="/loja/" ver_todos_texto="Veja todos"]
```

### Vitrine Masculinos / Femininos

```text
[attar_vitrine_genero titulo="Encontre sua fragrância" categoria_masculino="perfumes-masculinos" categoria_feminino="perfumes-femininos" tipo="mais_vendidos" limite="8" colunas="4"]
```

Também é possível definir URLs diferentes para cada aba:

```text
[attar_vitrine_genero ver_todos_masculino_url="/categoria/perfumes-masculinos/" ver_todos_feminino_url="/categoria/perfumes-femininos/"]
```

### Catálogo completo

```text
[attar_catalogo produtos_por_pagina="12" colunas="4" mostrar_titulo="sim" mostrar_conteudo="sim" mostrar_categorias="nao" mostrar_breadcrumb="sim"]
```

O catálogo inclui ordenação, filtros, contador de resultados, filtros ativos, limpeza rápida, breadcrumb, carregamento progressivo e conteúdo editorial superior e inferior.

### Produtos relacionados

```text
[attar_produtos_relacionados titulo="Produtos relacionados" limite="4" colunas="4" ver_todos_texto="Ver todos"]
```

Dentro de uma página de produto, o produto atual é detectado automaticamente.

Também é possível informar um produto manualmente:

```text
[attar_produtos_relacionados produto_id="123"]
```

### Avaliações

```text
[attar_avaliacoes_produto titulo="Avaliações do produto"]
```

O sistema utiliza as avaliações nativas do WooCommerce.

Usuários autenticados podem:

* selecionar uma nota de 1 a 5 estrelas;
* escrever uma avaliação;
* enviar até cinco imagens;
* usar arquivos JPG, PNG ou WebP;
* enviar imagens de até 5 MB cada.

As avaliações ficam sujeitas à moderação antes da publicação.

### PDP completa

```text
[attar_produto]
```

O shortcode deve ser usado no contexto de uma página individual de produto e detecta automaticamente o produto atual.

A PDP customizada inclui, quando os dados estão disponíveis:

* breadcrumb;
* galeria de imagens;
* marca;
* título;
* wishlist;
* avaliações;
* preço normal e promocional;
* percentual de desconto;
* preço no Pix;
* parcelamento;
* descrição curta;
* progresso de frete grátis;
* formulário de compra do WooCommerce;
* disponibilidade;
* SKU;
* categorias;
* concentração;
* subfamílias;
* família olfativa;
* descrição completa;
* Compre Junto;
* pirâmide olfativa;
* ocasiões;
* instruções de uso.

## Tipos de consulta

| Valor           | Comportamento                                        |
| --------------- | ---------------------------------------------------- |
| `mais_vendidos` | Ordena pelos produtos com maior `total_sales`        |
| `lancamentos`   | Busca produtos com tag `lancamento` ou `lancamentos` |
| `ofertas`       | Busca produtos atualmente em promoção                |
| `recentes`      | Ordena pelos produtos mais recentes                  |
| `destaques`     | Busca produtos marcados como destaque no WooCommerce |
| `aleatorios`    | Exibe produtos em ordem aleatória                    |
| `ids`           | Usa os IDs informados manualmente                    |

## Catálogo e filtros

O `[attar_catalogo]` suporta filtros por faixa de preço, marca, categoria, família olfativa, volume, concentração, gênero, disponibilidade em estoque e produtos em oferta.

A ordenação permite:

* mais vendidos;
* mais recentes;
* menor preço;
* maior preço;
* melhor avaliados.

Quando não existem atributos globais equivalentes, o plugin registra taxonomias administrativas próprias:

```text
abs_familia_olfativa
abs_volume_ml
```

Essas taxonomias não possuem arquivos públicos.

## SEO

O plugin possui tratamento específico para URLs geradas pelo catálogo.

Requisições com parâmetros `abs_*` recebem:

```text
noindex, follow
```

Além disso, o canonical aponta para a URL limpa do arquivo atual.

Quando o **Yoast SEO** está ativo, o plugin utiliza os filtros do próprio Yoast. Caso contrário, um canonical de fallback é impresso no `<head>`.

Isso reduz o risco de indexação de combinações de filtros e duplicação de URLs de catálogo.

## Conteúdo de categorias

Para conteúdo superior, são reconhecidos:

```text
conteudo_acima_dobra
conteudo_categoria_curto
descricao_curta
```

Para conteúdo inferior:

```text
conteudo_abaixo_dobra
conteudo_categoria_completo
conteudo_seo
```

O plugin também utiliza a descrição nativa da categoria quando disponível.

Os valores podem vir de ACF ou de post/term meta.

## Campos de produto

Entre os campos suportados estão:

```text
marca
concentracao
familia_olfativa
genero_perfume
badge_texto
badge_cor
```

Existem fallbacks para diferentes nomes de atributos e metadados usados em instalações WooCommerce.

O `badge_cor` pode receber um hexadecimal completo:

```text
#3A2118
```

## Campos da PDP

A página de produto possui campos próprios salvos pelo plugin para:

* Como usar;
* notas de saída;
* notas de coração;
* notas de fundo;
* ocasiões;
* combinações do Compre Junto.

O Compre Junto pode armazenar o produto complementar e, quando necessário, a variação específica que será utilizada.

## Compre Junto

A versão 2.4.2 possui suporte a produtos simples e variáveis.

O fluxo valida:

* produto principal;
* produto complementar;
* variação configurada;
* disponibilidade;
* estoque;
* possibilidade de compra.

Quando existe apenas uma variação comprável no produto complementar, ela pode ser selecionada automaticamente no painel.

Quando existem várias variações, o administrador escolhe qual delas fará parte da combinação.

A inclusão dos dois itens no carrinho é executada via AJAX e protegida por nonce.

## Avaliações com fotos

O sistema de avaliações utiliza comentários do tipo `review` do WooCommerce.

Cada usuário autenticado pode publicar uma avaliação por produto.

As imagens enviadas são adicionadas à Media Library e vinculadas à avaliação.

O ALT de cada foto é gerado no formato:

```text
Título do produto + número único
```

Ao excluir permanentemente uma avaliação, as imagens pertencentes a ela também são removidas.

## Painel administrativo

Após a ativação, o plugin adiciona o menu:

```text
Attar Storefront
```

O painel possui quatro áreas principais:

### Visão geral

Lista os shortcodes disponíveis e procura ocorrências em páginas, posts e templates do Elementor.

Isso facilita descobrir em quais URLs e templates cada shortcode está sendo utilizado.

### Links

Permite configurar URLs padrão para:

* catálogo / todos os produtos;
* vitrines;
* aba masculina;
* aba feminina;
* produtos relacionados.

### SVGs olfativos

Permite associar um SVG a cada família olfativa encontrada no catálogo.

O upload de SVG é liberado apenas para usuários com permissão administrativa.

> Utilize somente arquivos SVG confiáveis.

### Avaliações

Permite visualizar avaliações pendentes e aprovadas, incluindo as fotos enviadas pelos clientes.

## Hooks e filtros

### Alterar desconto Pix

```php
add_filter( 'abs_storefront_pix_discount', function ( $discount, $product ) {
    return 5;
}, 10, 2 );
```

### Alterar número de parcelas

```php
add_filter( 'abs_storefront_installments', function ( $installments, $product ) {
    return 10;
}, 10, 2 );
```

### Alterar faixas de preço

```php
add_filter( 'abs_storefront_price_ranges', function ( $ranges ) {
    return array(
        '0-250'   => 'Até R$ 250',
        '250-500' => 'R$ 250 – R$ 500',
        '500-'    => 'Acima de R$ 500',
    );
} );
```

### Alterar consultas das vitrines

```php
add_filter( 'abs_storefront_query_args', function ( $args, $settings ) {
    return $args;
}, 10, 2 );
```

### Alterar consulta do catálogo

```php
add_filter( 'abs_storefront_catalog_query_args', function ( $args, $state ) {
    return $args;
}, 10, 2 );
```

## Integrações opcionais

O plugin possui detecção condicional para recursos de terceiros.

### Yoast SEO

Quando disponível, utiliza:

```text
wpseo_robots
wpseo_canonical
```

### TI WooCommerce Wishlist

Quando o shortcode da wishlist está disponível, a PDP exibe o botão de favoritos.

### Simulador de frete

Quando existe:

```text
[wc_shipping_simulator]
```

a PDP pode renderizar o calculador de frete.

## Segurança

Algumas práticas utilizadas no projeto:

* verificação de nonces em operações sensíveis;
* sanitização de parâmetros de shortcode;
* sanitização de filtros vindos da URL;
* verificação de permissões administrativas;
* escaping da saída;
* restrição de tipos e tamanho das imagens de avaliações;
* validação de produto e variação antes de adicionar combinações ao carrinho;
* upload de SVG restrito a administradores.

## Performance e rastreabilidade

As vitrines são renderizadas no servidor, mantendo os produtos disponíveis no HTML inicial.

O carregamento progressivo do catálogo mantém uma URL real para a próxima página. Dessa forma, a navegação continua funcionando mesmo sem JavaScript e permanece rastreável.

Os assets do storefront são carregados somente quando necessários pelos componentes do plugin.

## Compatibilidade

A implementação original considera cenários com:

* Elementor;
* produtos simples;
* produtos variáveis;
* WooCommerce Variation Swatches;
* TI WooCommerce Wishlist;
* Yoast SEO;
* simuladores de frete via shortcode.

Nem todas essas extensões são obrigatórias.

## Changelog

### 2.4.2

* Avaliações reposicionadas na coluna direita da página individual, após “Como usar”.
* Compre Junto passa a salvar e utilizar a variação complementar exata.
* Preço, imagem, atributos e estoque passam a considerar a variação configurada.
* Seleção automática quando existe apenas uma variação comprável.
* Sincronização da escolha de variação do produto principal.
* Compatibilidade mantida com combinações salvas por versões anteriores.

O histórico completo está disponível no `readme.txt`.

## Contribuindo

Contribuições são bem-vindas.

1. Faça um fork.
2. Crie uma branch:

```bash
git checkout -b feature/minha-melhoria
```

3. Faça seus commits:

```bash
git commit -m "feat: adiciona minha melhoria"
```

4. Envie sua branch:

```bash
git push origin feature/minha-melhoria
```

5. Abra um Pull Request descrevendo o problema e a implementação.

Correções, melhorias de acessibilidade, compatibilidade, performance, SEO e experiência WooCommerce são especialmente bem-vindas.

## Licença

Este projeto é distribuído sob a licença **GPL v2 ou posterior**, seguindo o modelo de licenciamento informado pelo plugin.

Consulte o arquivo de licença do repositório para mais detalhes.

## Sobre a Attar Brasil

O **Attar Brasil Storefront** nasceu como uma solução interna para uma operação real de e-commerce, com foco em perfumaria e experiência de compra no WooCommerce.

A publicação deste projeto tem como objetivo compartilhar parte da arquitetura desenvolvida, incentivar colaboração e servir de referência para outros desenvolvedores WordPress e WooCommerce.

---

Desenvolvido com WordPress, WooCommerce, PHP, JavaScript e CSS.
