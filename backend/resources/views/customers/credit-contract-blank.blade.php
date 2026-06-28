<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Contrato de Crediário — Em Branco</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; background: #fff; }
  .page { max-width: 760px; margin: 0 auto; padding: 18px 22px; }
  .page-break { page-break-before: always; padding-top: 18px; }
  h1.title { font-size: 13px; font-weight: bold; text-align: center; text-transform: uppercase;
             letter-spacing: 0.08em; margin-bottom: 14px; }
  h2 { font-size: 9.5px; font-weight: bold; text-transform: uppercase;
       letter-spacing: 0.06em; border-bottom: 1px solid #555;
       padding-bottom: 2px; margin: 10px 0 5px; }
  .header { overflow: hidden; margin-bottom: 14px; border-bottom: 2px solid #111; padding-bottom: 8px; }
  .header .left { float: left; }
  .header .right { float: right; text-align: right; }
  .tenant-name { font-size: 15px; font-weight: bold; }
  .doc-sub { font-size: 8.5px; color: #555; margin-top: 1px; }
  .doc-title { font-size: 11px; font-weight: bold; margin-top: 3px; }
  .clearfix::after { content: ''; display: table; clear: both; }
  .grid { overflow: hidden; }
  .col2 { float: left; width: 50%; padding-right: 8px; }
  .col3 { float: left; width: 33.33%; padding-right: 8px; }
  .col4 { float: left; width: 25%; padding-right: 8px; }
  .field { margin-bottom: 5px; }
  .field label { display: block; font-size: 7.5px; color: #666; text-transform: uppercase; letter-spacing: 0.04em; }
  .field .blank { border-bottom: 1px solid #aaa; min-height: 16px; }
  table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9px; }
  th { background: #eee; text-align: left; padding: 3px 5px; font-size: 8px; font-weight: bold;
       text-transform: uppercase; border: 1px solid #ccc; }
  td.blank { height: 22px; border: 1px solid #ddd; }
  .section-box { border: 1px solid #ccc; border-radius: 2px; padding: 6px 8px; margin: 5px 0; }
  .contract-text { font-size: 8.5px; line-height: 1.5; text-align: justify; margin: 5px 0; }
  .contract-text p { margin-bottom: 4px; }
  .clause { font-weight: bold; }
  .signatures { overflow: hidden; margin-top: 20px; }
  .sig-block { float: left; width: 30%; margin-right: 3%; }
  .sig-block:last-child { margin-right: 0; }
  .sig-line { border-top: 1px solid #333; margin-top: 36px; margin-bottom: 3px; }
  .sig-name { font-size: 8px; text-align: center; color: #555; }
  .sig-label { font-size: 7.5px; color: #888; text-align: center; }
  .footer { margin-top: 14px; border-top: 1px solid #ccc; padding-top: 5px;
            font-size: 7.5px; color: #666; text-align: center; }
  .stamp-box { border: 2px dashed #999; min-height: 60px; margin-top: 8px;
               display: flex; align-items: center; justify-content: center;
               font-size: 8px; color: #bbb; }
  @page { margin: 1.2cm 1.5cm; }
</style>
</head>
<body>

{{-- PÁGINA 1 --}}
<div class="page">
  <div class="header clearfix">
    <div class="left">
      <div class="tenant-name">_________________________________</div>
      <div class="doc-sub">CNPJ: ____________________________</div>
    </div>
    <div class="right">
      <div class="doc-title">CONTRATO DE CREDIÁRIO</div>
      <div class="doc-sub">Data: _____ / _____ / _________</div>
      <div class="doc-sub">Cód. Cliente: __________</div>
    </div>
  </div>

  <h2>Dados Cadastrais</h2>
  <div class="grid clearfix">
    <div class="col2"><div class="field"><label>Nome Completo / Razão Social</label><div class="blank"></div></div></div>
    <div class="col2"><div class="field"><label>CPF / CNPJ</label><div class="blank"></div></div></div>
  </div>
  <div class="grid clearfix">
    <div class="col3"><div class="field"><label>RG</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Data de Nascimento</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Estado Civil</label><div class="blank"></div></div></div>
  </div>
  <div class="grid clearfix">
    <div class="col3"><div class="field"><label>Profissão</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Empresa / Empregador</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Renda Mensal (R$)</label><div class="blank"></div></div></div>
  </div>
  <div class="grid clearfix">
    <div class="col2"><div class="field"><label>Endereço (Rua, Nº)</label><div class="blank"></div></div></div>
    <div class="col4"><div class="field"><label>Bairro</label><div class="blank"></div></div></div>
    <div class="col4"><div class="field"><label>CEP</label><div class="blank"></div></div></div>
  </div>
  <div class="grid clearfix">
    <div class="col3"><div class="field"><label>Cidade</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Estado (UF)</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Tipo de Moradia</label><div class="blank"></div></div></div>
  </div>
  <div class="grid clearfix">
    <div class="col2"><div class="field"><label>Telefone / Celular</label><div class="blank"></div></div></div>
    <div class="col2"><div class="field"><label>E-mail</label><div class="blank"></div></div></div>
  </div>

  <h2>Cônjuge</h2>
  <div class="grid clearfix">
    <div class="col2"><div class="field"><label>Nome do Cônjuge</label><div class="blank"></div></div></div>
    <div class="col2"><div class="field"><label>CPF do Cônjuge</label><div class="blank"></div></div></div>
  </div>
  <div class="grid clearfix">
    <div class="col3"><div class="field"><label>Empresa / Empregador</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Renda Mensal (R$)</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Telefone</label><div class="blank"></div></div></div>
  </div>

  <h2>Resumo de Crédito</h2>
  <div class="grid clearfix">
    <div class="col3"><div class="field"><label>Limite de Crédito (R$)</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Score de Crédito</label><div class="blank"></div></div></div>
    <div class="col3"><div class="field"><label>Situação SPC</label><div class="blank"></div></div></div>
  </div>
</div>

{{-- PÁGINA 2 --}}
<div class="page-break page">
  <div class="header clearfix">
    <div class="left"><div class="tenant-name">_________________________________</div></div>
    <div class="right"><div class="doc-title">CREDIÁRIO — PÁG. 2</div></div>
  </div>

  <h2>Bens Declarados</h2>
  <table>
    <thead><tr><th>Tipo</th><th>Descrição</th><th>Endereço / Placa</th><th>Valor Estimado</th></tr></thead>
    <tbody>
      @for($i = 0; $i < 3; $i++)
        <tr><td class="blank"></td><td class="blank"></td><td class="blank"></td><td class="blank"></td></tr>
      @endfor
    </tbody>
  </table>

  <h2>Referências Bancárias</h2>
  <table>
    <thead><tr><th>Banco</th><th>Agência</th><th>Contato</th><th>Telefone</th><th>Consultado em</th></tr></thead>
    <tbody>
      @for($i = 0; $i < 3; $i++)
        <tr><td class="blank"></td><td class="blank"></td><td class="blank"></td><td class="blank"></td><td class="blank"></td></tr>
      @endfor
    </tbody>
  </table>

  <h2>Autorização de Compra (Dependentes)</h2>
  <table>
    <thead><tr><th>#</th><th>Nome</th><th>CPF</th><th>Parentesco</th><th>Limite</th><th>Válido até</th></tr></thead>
    <tbody>
      @for($i = 0; $i < 4; $i++)
        <tr><td class="blank" style="width:5%"></td><td class="blank"></td><td class="blank"></td><td class="blank"></td><td class="blank"></td><td class="blank"></td></tr>
      @endfor
    </tbody>
  </table>

  <h2>Avalista</h2>
  <div class="section-box">
    <div class="grid clearfix">
      <div class="col2"><div class="field"><label>Nome</label><div class="blank"></div></div></div>
      <div class="col2"><div class="field"><label>CPF/CNPJ</label><div class="blank"></div></div></div>
    </div>
    <div class="grid clearfix">
      <div class="col3"><div class="field"><label>Profissão</label><div class="blank"></div></div></div>
      <div class="col3"><div class="field"><label>Renda Mensal (R$)</label><div class="blank"></div></div></div>
      <div class="col3"><div class="field"><label>Relação com o Cliente</label><div class="blank"></div></div></div>
    </div>
    <div class="field"><label>Endereço Completo</label><div class="blank" style="min-height:20px;"></div></div>
  </div>
</div>

{{-- PÁGINA 3 --}}
<div class="page-break page">
  <div class="header clearfix">
    <div class="left"><div class="tenant-name">_________________________________</div></div>
    <div class="right"><div class="doc-title">CONTRATO DE CREDIÁRIO</div></div>
  </div>

  <h1 class="title">Contrato de Abertura de Crédito e Prestação de Serviços</h1>

  <div class="contract-text">
    <p><span class="clause">CREDOR:</span> ____________________________________________, inscrita no CNPJ sob o nº _______________________, doravante denominado simplesmente <strong>CREDOR</strong>.</p>
    <p><span class="clause">DEVEDOR:</span> ____________________________________________, inscrito no CPF/CNPJ sob o nº _______________________,  doravante denominado simplesmente <strong>DEVEDOR</strong>.</p>

    <p><span class="clause">CLÁUSULA 1ª — DO OBJETO</span></p>
    <p>O presente instrumento tem por objeto a abertura de crédito rotativo ao DEVEDOR, no limite de <strong>R$ ___________,___</strong>, para aquisição de mercadorias no estabelecimento do CREDOR.</p>

    <p><span class="clause">CLÁUSULA 2ª — DOS ENCARGOS</span></p>
    <p>Sobre o saldo devedor incidirão juros de <strong>{{ $settings['contract_interest_rate'] ?? '1,00' }}% ao mês</strong>, cobrados a partir do vencimento de cada parcela. Em caso de inadimplência, será cobrada multa de <strong>{{ $settings['contract_fine_rate'] ?? '2,00' }}%</strong> sobre o valor em aberto.</p>

    <p><span class="clause">CLÁUSULA 3ª — DO PAGAMENTO</span></p>
    <p>As compras realizadas serão parceladas conforme acordado no ato de cada venda, com vencimento a cada {{ $settings['contract_credit_days'] ?? 30 }} dias. O DEVEDOR compromete-se a honrar o pagamento nas datas de vencimento.</p>

    <p><span class="clause">CLÁUSULA 4ª — DAS AUTORIZAÇÕES</span></p>
    <p>O DEVEDOR autoriza o CREDOR a consultar o SPC/Serasa e demais órgãos de proteção ao crédito, bem como a inscrever seu nome nesses cadastros em caso de inadimplência.</p>

    <p><span class="clause">CLÁUSULA 5ª — DOS DADOS PESSOAIS (LGPD)</span></p>
    <p>Os dados pessoais fornecidos neste contrato serão tratados em conformidade com a Lei Geral de Proteção de Dados (Lei nº 13.709/2018), utilizados exclusivamente para análise de crédito e gestão de relacionamento.</p>

    <p><span class="clause">CLÁUSULA 6ª — DA ELEIÇÃO DE FORO</span></p>
    <p>As partes elegem o foro da Comarca de <strong>________________</strong>, para dirimir quaisquer controvérsias oriundas deste instrumento.</p>

    <p>________________, _____ de ________________ de ________.</p>
  </div>

  <div class="signatures clearfix">
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name">___________________________</div>
      <div class="sig-label">DEVEDOR · CPF: _______________</div>
    </div>
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name">___________________________</div>
      <div class="sig-label">AVALISTA / TESTEMUNHA</div>
    </div>
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name">___________________________</div>
      <div class="sig-label">CREDOR · Responsável</div>
    </div>
  </div>

  <div class="stamp-box"><span>Carimbo / Assinatura do Credor</span></div>

  <div class="footer">
    CONTRATO DE CREDIÁRIO · Gerado em {{ now()->format('d/m/Y H:i') }}
  </div>
</div>

</body>
</html>
