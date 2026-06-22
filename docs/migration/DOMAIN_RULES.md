# DOMAIN RULES

Objetivo:
Transformar o ERP de moda em ERP para material de construção.

## Regras de Negócio

### Unidades Permitidas

- UN
- M
- M²
- M³
- KG
- LT
- CX
- SC

### Quantidades

Todo o sistema deve suportar:

DECIMAL(15,3)

Exemplos:

- 3.750 m²
- 2.500 m
- 15.250 kg

### Estoque

O estoque deve suportar quantidades fracionadas.

Exemplos:

- 152.500 kg de areia
- 325.750 m² de piso
- 48.500 metros de cabo

### Arquitetura

Manter arquitetura existente:

Produto
→ Variante
→ Atributo

### Atributos Técnicos

Substituir conceitos de moda por:

- Bitola
- Diâmetro
- Comprimento
- Espessura
- Volume
- Potência
- Tensão
- Material
- Cor
- Acabamento
- Marca

### Categorias Principais

- Cimento e Argamassa
- Pisos e Revestimentos
- Hidráulica
- Elétrica
- Ferragens
- Ferramentas
- Tintas
- Madeira
- Cobertura
- Acabamentos

### Campos Obrigatórios

Produtos devem suportar:

- SKU
- NCM
- Unidade
- Categoria
- Fornecedor
- Estoque
- Custo
- Preço

### Conceitos Proibidos

Não utilizar:

- gender
- ProductGenderEnum
- moda
- vestuário
- coleção
- estação
- grade de roupa
- tamanho de roupa
- gênero masculino
- gênero feminino

Toda nova funcionalidade deve respeitar estas regras.
