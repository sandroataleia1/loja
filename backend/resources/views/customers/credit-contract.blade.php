<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Contrato de Crediário{{ isset($customer) ? ' — '.$customer->name : '' }}</title>
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
  h3 { font-size: 9px; font-weight: bold; margin: 7px 0 3px; }
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
  .field { margin-bottom: 4px; }
  .field label { display: block; font-size: 7.5px; color: #666; text-transform: uppercase; letter-spacing: 0.04em; }
  .field .value { border-bottom: 1px solid #888; min-height: 14px; padding-bottom: 1px; font-size: 9.5px; }
  .field .blank { border-bottom: 1px solid #aaa; min-height: 14px; }
  table { width: 100%; border-collapse: collapse; margin-top: 4px; font-size: 9px; }
  th { background: #eee; text-align: left; padding: 3px 5px; font-size: 8px; font-weight: bold;
       text-transform: uppercase; border: 1px solid #ccc; }
  td { padding: 3px 5px; border: 1px solid #ddd; height: 18px; vertical-align: middle; }
  td.blank { height: 20px; }
  .section-box { border: 1px solid #ccc; border-radius: 2px; padding: 6px 8px; margin: 5px 0; }
  .contract-text { font-size: 8.5px; line-height: 1.5; text-align: justify; margin: 5px 0; }
  .contract-text p { margin-bottom: 4px; }
  .clause { font-weight: bold; }
  .signatures { overflow: hidden; margin-top: 20px; }
  .sig-block { float: left; width: 30%; margin-right: 3%; }
  .sig-block:last-child { margin-right: 0; }
  .sig-line { border-top: 1px solid #333; margin-top: 36px; margin-bottom: 3px; }
  .sig-name { font-size: 8px; text-align: center; }
  .sig-label { font-size: 7.5px; color: #666; text-align: center; }
  .buyers-table td { font-size: 8.5px; }
  .footer { margin-top: 14px; border-top: 1px solid #ccc; padding-top: 5px;
            font-size: 7.5px; color: #666; text-align: center; }
  .stamp-box { border: 2px dashed #999; min-height: 60px; margin-top: 8px;
               display: flex; align-items: center; justify-content: center;
               font-size: 8px; color: #bbb; }
  @page { margin: 1.2cm 1.5cm; }
</style>
</head>
<body>

{{-- ══════════════════════════════════════════════════════════
     PÁGINA 1 — Ficha Completa + Resumo Financeiro
     ══════════════════════════════════════════════════════════ --}}
<div class="page">

  <div class="header clearfix">
    <div class="left">
      <div class="tenant-name">{{ $settings['company_name'] ?? 'Alves Shopping' }}</div>
      @if(!empty($settings['cnpj']))
        <div class="doc-sub">CNPJ: {{ $settings['cnpj'] }}</div>
      @endif
      @if(!empty($settings['address']))
        <div class="doc-sub">{{ $settings['address'] }}</div>
      @endif
    </div>
    <div class="right">
      <div class="doc-title">CONTRATO DE CREDIÁRIO</div>
      <div class="doc-sub">Data: {{ now()->format('d/m/Y') }}</div>
      <div class="doc-sub">Cód. Cliente: {{ $customer->code ?? '—' }}</div>
    </div>
  </div>

  <h2>Dados Cadastrais</h2>
  <div class="grid clearfix">
    <div class="col2">
      <div class="field">
        <label>Nome Completo / Razão Social</label>
        <div class="value">{{ $customer->name }}</div>
      </div>
    </div>
    <div class="col2">
      <div class="field">
        <label>{{ $customer->person_type === 'COMPANY' ? 'CNPJ' : 'CPF' }}</label>
        <div class="value">{{ $customer->document ?? '—' }}</div>
      </div>
    </div>
  </div>

  <div class="grid clearfix">
    <div class="col3">
      <div class="field">
        <label>RG</label>
        <div class="value">{{ $customer->rg ?? '—' }}</div>
      </div>
    </div>
    <div class="col3">
      <div class="field">
        <label>Data de Nascimento</label>
        <div class="value">{{ $customer->birth_date?->format('d/m/Y') ?? '—' }}</div>
      </div>
    </div>
    <div class="col3">
      <div class="field">
        <label>Estado Civil</label>
        <div class="value">{{ $customer->maritalStatusLabel() ?? '—' }}</div>
      </div>
    </div>
  </div>

  <div class="grid clearfix">
    <div class="col3">
      <div class="field">
        <label>Profissão</label>
        <div class="value">{{ $customer->profession ?? '—' }}</div>
      </div>
    </div>
    <div class="col3">
      <div class="field">
        <label>Empresa / Empregador</label>
        <div class="value">{{ $customer->employer ?? '—' }}</div>
      </div>
    </div>
    <div class="col3">
      <div class="field">
        <label>Renda Mensal</label>
        <div class="value">{{ $customer->monthly_income ? 'R$ '.number_format($customer->monthly_income, 2, ',', '.') : '—' }}</div>
      </div>
    </div>
  </div>

  @if($customer->addresses->first())
    @php $addr = $customer->addresses->first() @endphp
    <div class="grid clearfix">
      <div class="col2">
        <div class="field">
          <label>Endereço</label>
          <div class="value">{{ $addr->street }}, {{ $addr->number }}{{ $addr->complement ? ' — '.$addr->complement : '' }}</div>
        </div>
      </div>
      <div class="col4">
        <div class="field"><label>Bairro</label><div class="value">{{ $addr->district }}</div></div>
      </div>
      <div class="col4">
        <div class="field"><label>CEP</label><div class="value">{{ $addr->zipcode }}</div></div>
      </div>
    </div>
    <div class="grid clearfix">
      <div class="col3">
        <div class="field"><label>Cidade</label><div class="value">{{ $addr->city }}</div></div>
      </div>
      <div class="col3">
        <div class="field"><label>Estado</label><div class="value">{{ $addr->state }}</div></div>
      </div>
      <div class="col3">
        <div class="field"><label>Tipo de Moradia</label>
          <div class="value">{{ match($customer->housing_type) {
            'OWN' => 'Própria', 'RENTED' => 'Alugada', 'FINANCED' => 'Financiada',
            'FAMILY' => 'Familiar', default => '—'
          } }}</div>
        </div>
      </div>
    </div>
  @endif

  <div class="grid clearfix">
    @if($customer->contacts->isNotEmpty())
      <div class="col2">
        <div class="field">
          <label>Telefone / Celular</label>
          <div class="value">{{ $customer->contacts->map(fn($c) => $c->value)->implode(' / ') }}</div>
        </div>
      </div>
    @endif
    <div class="col2">
      <div class="field">
        <label>E-mail</label>
        <div class="value">{{ $customer->email ?? '—' }}</div>
      </div>
    </div>
  </div>

  <h2>Resumo Financeiro</h2>
  <div class="section-box">
    <div class="grid clearfix">
      <div class="col3">
        <div class="field"><label>Limite de Crédito</label>
          <div class="value" style="font-weight:bold;">
            R$ {{ number_format(($customer->credit_limit_cents ?? 0) / 100, 2, ',', '.') }}
          </div>
        </div>
      </div>
      <div class="col3">
        <div class="field"><label>Score de Crédito</label>
          <div class="value">{{ $customer->credit_score ?? '—' }} / 10</div>
        </div>
      </div>
      <div class="col3">
        <div class="field"><label>Última compra</label>
          <div class="value">{{ $customer->last_purchase_at?->format('d/m/Y') ?? '—' }}</div>
        </div>
      </div>
    </div>
    <div class="grid clearfix">
      <div class="col3">
        <div class="field"><label>Situação SPC</label>
          <div class="value">{{ $customer->spc_status?->label() ?? 'Não consultado' }}</div>
        </div>
      </div>
      <div class="col3">
        <div class="field"><label>Data Consulta SPC</label>
          <div class="value">{{ $customer->spc_consulted_at?->format('d/m/Y') ?? '—' }}</div>
        </div>
      </div>
      <div class="col3">
        <div class="field"><label>Data Análise Crédito</label>
          <div class="value">{{ $customer->credit_analysis_date?->format('d/m/Y') ?? '—' }}</div>
        </div>
      </div>
    </div>
  </div>

</div>

{{-- ══════════════════════════════════════════════════════════
     PÁGINA 2 — Crediário, Bens, Cartões, Autorização de Compra
     ══════════════════════════════════════════════════════════ --}}
<div class="page-break page">

  <div class="header clearfix">
    <div class="left"><div class="tenant-name">{{ $settings['company_name'] ?? 'Alves Shopping' }}</div></div>
    <div class="right">
      <div class="doc-title">CREDIÁRIO — PÁG. 2</div>
      <div class="doc-sub">{{ $customer->name }} · Cód: {{ $customer->code ?? '—' }}</div>
    </div>
  </div>

  <h2>Cônjuge</h2>
  <div class="grid clearfix">
    <div class="col2">
      <div class="field"><label>Nome do Cônjuge</label>
        <div class="value">{{ $customer->spouse_name ?? '—' }}</div></div>
    </div>
    <div class="col2">
      <div class="field"><label>CPF do Cônjuge</label>
        <div class="value">{{ $customer->spouse_cpf ?? $customer->spouse_document ?? '—' }}</div></div>
    </div>
  </div>
  <div class="grid clearfix">
    <div class="col3">
      <div class="field"><label>Empresa / Empregador</label>
        <div class="value">{{ $customer->spouse_employer ?? '—' }}</div></div>
    </div>
    <div class="col3">
      <div class="field"><label>Renda Mensal</label>
        <div class="value">{{ $customer->spouse_monthly_income ? 'R$ '.number_format($customer->spouse_monthly_income, 2, ',', '.') : '—' }}</div></div>
    </div>
    <div class="col3">
      <div class="field"><label>Telefone</label>
        <div class="value">{{ $customer->spouse_phone ?? '—' }}</div></div>
    </div>
  </div>

  {{-- Bens Materiais --}}
  @if($customer->assets->isNotEmpty())
    <h2>Bens Declarados</h2>
    <table>
      <thead>
        <tr>
          <th>Tipo</th><th>Descrição</th><th>Endereço / Placa</th><th>Valor Estimado</th>
        </tr>
      </thead>
      <tbody>
        @foreach($customer->assets as $asset)
          <tr>
            <td>{{ $asset->asset_type }}</td>
            <td>{{ $asset->description }}</td>
            <td>{{ $asset->address ?? '—' }}</td>
            <td>{{ $asset->estimated_value_cents ? 'R$ '.number_format($asset->estimated_value_cents/100,2,',','.') : '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  {{-- Cartões --}}
  @if($customer->cards->isNotEmpty())
    <h2>Cartões de Crédito</h2>
    <div class="section-box">
      {{ $customer->cards->pluck('card_brand')->implode(' · ') }}
    </div>
  @endif

  {{-- Referências Bancárias --}}
  @if($customer->bankReferences->isNotEmpty())
    <h2>Referências Bancárias</h2>
    <table>
      <thead>
        <tr><th>Banco</th><th>Agência</th><th>Contato</th><th>Telefone</th><th>Consultado em</th></tr>
      </thead>
      <tbody>
        @foreach($customer->bankReferences as $ref)
          <tr>
            <td>{{ $ref->bank_name }}</td>
            <td>{{ $ref->bank_agency ?? '—' }}</td>
            <td>{{ $ref->contact_name ?? '—' }}</td>
            <td>{{ $ref->phone ?? '—' }}</td>
            <td>{{ $ref->consulted_at?->format('d/m/Y') ?? '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif

  {{-- Dependentes Autorizados --}}
  <h2>Autorização de Compra (Dependentes)</h2>
  @if($activeBuyers->isNotEmpty())
    <table class="buyers-table">
      <thead>
        <tr><th>#</th><th>Nome</th><th>CPF</th><th>Parentesco</th><th>Limite</th><th>Válido até</th></tr>
      </thead>
      <tbody>
        @foreach($activeBuyers as $i => $buyer)
          <tr>
            <td>{{ $i + 1 }}</td>
            <td>{{ $buyer->name }}</td>
            <td>{{ $buyer->cpf ?? '—' }}</td>
            <td>{{ $buyer->relationship ?? '—' }}</td>
            <td>{{ $buyer->credit_limit_cents ? 'R$ '.number_format($buyer->credit_limit_cents/100,2,',','.') : 'Sem limite' }}</td>
            <td>{{ $buyer->valid_until?->format('d/m/Y') ?? 'Sem prazo' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @else
    <p style="font-size:9px;color:#777;margin:4px 0;">Nenhum dependente autorizado cadastrado.</p>
  @endif

  {{-- Avalista --}}
  @if($guarantor)
    <h2>Avalista</h2>
    <div class="section-box">
      <div class="grid clearfix">
        <div class="col2">
          <div class="field"><label>Nome</label><div class="value">{{ $guarantor->name }}</div></div>
        </div>
        <div class="col2">
          <div class="field"><label>CPF/CNPJ</label><div class="value">{{ $guarantor->document }}</div></div>
        </div>
      </div>
      <div class="grid clearfix">
        <div class="col3">
          <div class="field"><label>Profissão</label><div class="value">{{ $guarantor->profession ?? '—' }}</div></div>
        </div>
        <div class="col3">
          <div class="field"><label>Renda</label>
            <div class="value">{{ $guarantor->monthly_income ? 'R$ '.number_format($guarantor->monthly_income,2,',','.') : '—' }}</div>
          </div>
        </div>
        <div class="col3">
          <div class="field"><label>Relação</label><div class="value">{{ $guarantor->relationship ?? '—' }}</div></div>
        </div>
      </div>
      @if($guarantor->street)
        <div class="field"><label>Endereço</label>
          <div class="value">{{ $guarantor->street }}, {{ $guarantor->number }} — {{ $guarantor->neighborhood }}, {{ $guarantor->city }}/{{ $guarantor->state }}</div>
        </div>
      @elseif($guarantor->address)
        <div class="field"><label>Endereço</label><div class="value">{{ $guarantor->address }}</div></div>
      @endif
    </div>
  @endif

</div>

{{-- ══════════════════════════════════════════════════════════
     PÁGINA 3 — Contrato de Prestação de Serviço (Assinável)
     ══════════════════════════════════════════════════════════ --}}
<div class="page-break page">

  <div class="header clearfix">
    <div class="left"><div class="tenant-name">{{ $settings['company_name'] ?? 'Alves Shopping' }}</div></div>
    <div class="right">
      <div class="doc-title">CONTRATO DE CREDIÁRIO</div>
      <div class="doc-sub">{{ now()->format('d/m/Y') }}</div>
    </div>
  </div>

  <h1 class="title">Contrato de Abertura de Crédito e Prestação de Serviços</h1>

  <div class="contract-text">
    <p>
      <span class="clause">CREDOR:</span> {{ $settings['company_name'] ?? 'Alves Shopping' }}
      @if(!empty($settings['cnpj'])), inscrita no CNPJ sob o nº {{ $settings['cnpj'] }}@endif,
      doravante denominado simplesmente <strong>CREDOR</strong>.
    </p>
    <p>
      <span class="clause">DEVEDOR:</span> {{ $customer->name }},
      {{ $customer->person_type === 'COMPANY' ? 'pessoa jurídica inscrita no CNPJ' : 'pessoa física inscrita no CPF' }}
      sob o nº {{ $customer->document ?? '________________' }},
      residente e domiciliado em
      {{ ($customer->addresses->first()->street ?? '') . ', ' . ($customer->addresses->first()->number ?? '') . ', ' . ($customer->addresses->first()->city ?? '') }}
      doravante denominado simplesmente <strong>DEVEDOR</strong>.
    </p>

    <p><span class="clause">CLÁUSULA 1ª — DO OBJETO</span></p>
    <p>O presente instrumento tem por objeto a abertura de crédito rotativo ao DEVEDOR, no limite de
       <strong>R$ {{ number_format(($customer->credit_limit_cents ?? 0)/100, 2, ',', '.') }}</strong>,
       para aquisição de mercadorias no estabelecimento do CREDOR, nas condições descritas abaixo.</p>

    <p><span class="clause">CLÁUSULA 2ª — DOS ENCARGOS</span></p>
    <p>Sobre o saldo devedor incidirão juros de <strong>{{ $settings['contract_interest_rate'] ?? '1,00' }}% ao mês</strong>,
       cobrados a partir do vencimento de cada parcela.
       Em caso de inadimplência, será cobrada multa de <strong>{{ $settings['contract_fine_rate'] ?? '2,00' }}%</strong>
       sobre o valor em aberto, além dos juros estabelecidos.</p>

    <p><span class="clause">CLÁUSULA 3ª — DO PAGAMENTO</span></p>
    <p>As compras realizadas serão divididas conforme acordado no ato de cada venda,
       com vencimento a cada {{ $settings['contract_credit_days'] ?? 30 }} dias.
       O DEVEDOR se compromete a honrar o pagamento das parcelas nas datas de vencimento.</p>

    <p><span class="clause">CLÁUSULA 4ª — DAS AUTORIZAÇÕES</span></p>
    <p>O DEVEDOR autoriza o CREDOR a consultar o SPC/Serasa e demais órgãos de proteção ao crédito,
       bem como a inscrever seu nome nesses cadastros em caso de inadimplência, nos termos da legislação vigente.</p>

    <p><span class="clause">CLÁUSULA 5ª — DOS DADOS PESSOAIS (LGPD)</span></p>
    <p>Os dados pessoais fornecidos neste contrato serão utilizados exclusivamente para fins de análise de crédito,
       gestão de relacionamento e comunicações referentes a compras e cobranças, em conformidade com a
       Lei Geral de Proteção de Dados (Lei nº 13.709/2018).</p>

    <p><span class="clause">CLÁUSULA 6ª — DA ELEIÇÃO DE FORO</span></p>
    <p>As partes elegem o foro da Comarca de
       <strong>{{ $settings['contract_city'] ?? 'Parauapebas' }}</strong>, Estado do Pará,
       para dirimir quaisquer controvérsias oriundas deste instrumento.</p>

    <p>{{ $settings['contract_city'] ?? 'Parauapebas' }}/PA, {{ now()->format('d') }} de {{ now()->translatedFormat('F') }} de {{ now()->format('Y') }}.</p>
  </div>

  {{-- Assinaturas --}}
  <div class="signatures clearfix">
    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name">{{ $customer->name }}</div>
      <div class="sig-label">DEVEDOR · CPF: {{ $customer->document ?? '—' }}</div>
    </div>

    @if($guarantor)
      <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-name">{{ $guarantor->name }}</div>
        <div class="sig-label">AVALISTA · CPF: {{ $guarantor->document ?? '—' }}</div>
      </div>
    @else
      <div class="sig-block">
        <div class="sig-line"></div>
        <div class="sig-name">Testemunha</div>
        <div class="sig-label">CPF: ______________________</div>
      </div>
    @endif

    <div class="sig-block">
      <div class="sig-line"></div>
      <div class="sig-name">{{ $settings['company_name'] ?? 'Alves Shopping' }}</div>
      <div class="sig-label">CREDOR · Responsável</div>
    </div>
  </div>

  {{-- Espaço para carimbo --}}
  <div class="stamp-box">
    <span>Carimbo / Assinatura do Credor</span>
  </div>

  <div class="footer">
    {{ $settings['company_name'] ?? 'Alves Shopping' }}
    @if(!empty($settings['cnpj'])) · CNPJ: {{ $settings['cnpj'] }}@endif
    @if(!empty($settings['address'])) · {{ $settings['address'] }}@endif
  </div>

</div>

</body>
</html>
