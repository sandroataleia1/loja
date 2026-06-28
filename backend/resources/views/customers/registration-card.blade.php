<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Ficha de Cadastro{{ isset($customer) && $customer ? ' — '.$customer->name : '' }}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; background: #fff; }
  .page { max-width: 760px; margin: 0 auto; padding: 20px; }
  h2 { font-size: 11px; font-weight: bold; margin: 12px 0 5px; text-transform: uppercase;
       border-bottom: 1px solid #333; padding-bottom: 2px; }
  .header { overflow: hidden; margin-bottom: 14px; }
  .header .left { float: left; }
  .header .right { float: right; text-align: right; font-size: 11px; }
  .tenant-name { font-size: 16px; font-weight: bold; }
  .doc-date { font-size: 9px; color: #555; margin-top: 2px; }
  .grid { overflow: hidden; margin-bottom: 6px; }
  .col { float: left; padding-right: 10px; }
  .col2 { width: 50%; }
  .col3 { width: 33.33%; }
  .col4 { width: 25%; }
  .field { margin-bottom: 5px; }
  .field label { display: block; font-size: 8.5px; color: #555; margin-bottom: 1px; }
  .field .value { border-bottom: 1px solid #999; min-height: 16px; font-size: 11px; }
  .field .blank { border-bottom: 1px solid #aaa; min-height: 16px; }
  .section-box { border: 1px solid #ccc; border-radius: 2px; padding: 7px 9px; margin-top: 3px; }
  .clearfix::after { content: ''; display: table; clear: both; }
  table { width: 100%; border-collapse: collapse; margin-top: 3px; font-size: 10px; }
  th { background: #f0f0f0; text-align: left; padding: 3px 5px; font-size: 9px; }
  td { padding: 3px 5px; border-bottom: 1px solid #e0e0e0; height: 20px; }
  .signatures { overflow: hidden; margin-top: 18px; }
  .sig-box { float: left; width: 30%; margin-right: 4%; border: 1px solid #bbb;
             border-radius: 2px; padding: 6px; text-align: center; }
  .sig-box:last-child { margin-right: 0; }
  .sig-line { border-top: 1px solid #555; margin: 28px 10px 3px; }
  .sig-label { font-size: 8.5px; color: #555; }
  .lgpd { margin-top: 12px; font-size: 8px; color: #555; line-height: 1.4;
           border-top: 1px solid #ccc; padding-top: 6px; }
  @media print { .print-btn { display: none; } }
  .print-btn { display: block; margin: 10px auto 0; padding: 7px 20px;
               background: #2563eb; color: #fff; border: none; border-radius: 4px;
               font-size: 12px; cursor: pointer; }
</style>
</head>
<body>
<div class="page">

  {{-- Cabeçalho --}}
  <div class="header clearfix">
    <div class="left">
      <div class="tenant-name">{{ $tenant?->name ?? 'Empresa' }}</div>
      @if($tenant?->document ?? null)
        <div style="font-size:9px;color:#666;">CNPJ: {{ $tenant->document }}</div>
      @endif
    </div>
    <div class="right">
      <strong>FICHA DE CADASTRO</strong>
      <div class="doc-date">Data: {{ $date }}</div>
      @if(!($blank ?? false) && ($customer->code ?? null))
        <div class="doc-date">Cód: {{ $customer->code }}</div>
      @endif
    </div>
  </div>

  {{-- 1. Dados Pessoais --}}
  <h2>1. Dados Pessoais</h2>
  <div class="grid clearfix">
    <div class="col col2 field">
      <label>Nome Completo</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ $customer->name ?? '' }}</div> @endif
    </div>
    <div class="col col2 field">
      <label>CPF / CNPJ</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ $customer->document ?? '' }}</div> @endif
    </div>
  </div>
  <div class="grid clearfix">
    <div class="col col3 field">
      <label>Data de Nascimento</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ ($customer->birth_date ?? null)?->format('d/m/Y') }}</div> @endif
    </div>
    <div class="col col3 field">
      <label>Sexo</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ $customer->gender ?? '' }}</div> @endif
    </div>
    <div class="col col3 field">
      <label>Estado Civil</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ $customer->civil_status ?? '' }}</div> @endif
    </div>
  </div>
  <div class="grid clearfix">
    <div class="col col2 field">
      <label>E-mail</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ $customer->email ?? '' }}</div> @endif
    </div>
    <div class="col col2 field">
      <label>RG / Identidade</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ $customer->identity_document ?? '' }}</div> @endif
    </div>
  </div>

  {{-- Campos customizados --}}
  @if(!empty($customFields))
  <h2>Informações Adicionais</h2>
  <div class="grid clearfix">
    @foreach($customFields as $key => $value)
    <div class="col col3 field">
      <label>{{ ucwords(str_replace('_', ' ', $key)) }}</label>
      <div class="value">{{ is_array($value) ? implode(', ', $value) : $value }}</div>
    </div>
    @endforeach
  </div>
  @endif

  {{-- 2. Endereço --}}
  <h2>2. Endereço</h2>
  @if(!($blank ?? false) && ($customer->addresses ?? collect())->isNotEmpty())
    @foreach($customer->addresses as $addr)
    <div class="section-box" style="margin-bottom:5px;">
      <div class="grid clearfix">
        <div class="col col3 field"><label>Logradouro / Nº</label><div class="value">{{ $addr->street }}, {{ $addr->number }}</div></div>
        <div class="col col3 field"><label>Bairro</label><div class="value">{{ $addr->neighborhood }}</div></div>
        <div class="col col3 field"><label>CEP</label><div class="value">{{ $addr->zip_code }}</div></div>
      </div>
      <div class="grid clearfix">
        <div class="col col2 field"><label>Cidade</label><div class="value">{{ $addr->city }}</div></div>
        <div class="col col2 field"><label>Estado</label><div class="value">{{ $addr->state }}</div></div>
      </div>
    </div>
    @endforeach
  @else
    <div class="section-box">
      <div class="grid clearfix">
        <div class="col col3 field"><label>Logradouro / Nº</label><div class="blank"></div></div>
        <div class="col col3 field"><label>Bairro</label><div class="blank"></div></div>
        <div class="col col3 field"><label>CEP</label><div class="blank"></div></div>
      </div>
      <div class="grid clearfix">
        <div class="col col2 field"><label>Cidade</label><div class="blank"></div></div>
        <div class="col col2 field"><label>Estado</label><div class="blank"></div></div>
      </div>
    </div>
  @endif

  {{-- 3. Contatos --}}
  <h2>3. Contatos</h2>
  @if(!($blank ?? false) && ($customer->contacts ?? collect())->isNotEmpty())
    <div class="grid clearfix">
      @foreach($customer->contacts as $contact)
      <div class="col col3 field">
        <label>{{ $contact->type ?? 'Contato' }}</label>
        <div class="value">{{ $contact->value ?? '' }}</div>
      </div>
      @endforeach
    </div>
  @else
    <div class="grid clearfix">
      <div class="col col3 field"><label>Telefone / Celular</label><div class="blank"></div></div>
      <div class="col col3 field"><label>WhatsApp</label><div class="blank"></div></div>
      <div class="col col3 field"><label>Outro</label><div class="blank"></div></div>
    </div>
  @endif

  {{-- 4. Cônjuge --}}
  <h2>4. Dados do Cônjuge</h2>
  <div class="section-box">
    <div class="grid clearfix">
      <div class="col col2 field">
        <label>Nome do Cônjuge</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->spouse_name ?? '' }}</div> @endif
      </div>
      <div class="col col2 field">
        <label>CPF do Cônjuge</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->spouse_document ?? '' }}</div> @endif
      </div>
    </div>
    <div class="grid clearfix">
      <div class="col col3 field">
        <label>Telefone</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->spouse_phone ?? '' }}</div> @endif
      </div>
      <div class="col col3 field">
        <label>Profissão</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->spouse_profession ?? '' }}</div> @endif
      </div>
      <div class="col col3 field">
        <label>Renda Mensal</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ ($customer->spouse_income ?? null) ? 'R$ '.number_format($customer->spouse_income, 2, ',', '.') : '' }}</div> @endif
      </div>
    </div>
  </div>

  {{-- 5. Avalista --}}
  <h2>5. Avalista / Fiador</h2>
  <div class="section-box">
    <div class="grid clearfix">
      <div class="col col2 field">
        <label>Nome do Avalista</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->guarantor_name ?? '' }}</div> @endif
      </div>
      <div class="col col2 field">
        <label>CPF do Avalista</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->guarantor_document ?? '' }}</div> @endif
      </div>
    </div>
    <div class="grid clearfix">
      <div class="col col3 field">
        <label>Telefone</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->guarantor_phone ?? '' }}</div> @endif
      </div>
      <div class="col col3 field">
        <label>Profissão</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->guarantor_profession ?? '' }}</div> @endif
      </div>
      <div class="col col3 field">
        <label>Renda Mensal</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ ($customer->guarantor_income ?? null) ? 'R$ '.number_format($customer->guarantor_income, 2, ',', '.') : '' }}</div> @endif
      </div>
    </div>
    <div class="grid clearfix">
      <div class="col field" style="width:100%">
        <label>Endereço do Avalista</label>
        @if($blank ?? false) <div class="blank"></div>
        @else <div class="value">{{ $customer->guarantor_address ?? '' }}</div> @endif
      </div>
    </div>
  </div>

  {{-- 6. Referências Comerciais --}}
  <h2>6. Referências Comerciais</h2>
  <table>
    <tr><th>Estabelecimento</th><th>Telefone</th><th>Valor Crédito</th></tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
    <tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
  </table>

  {{-- 7. Condições de Crédito --}}
  <h2>7. Condições de Crédito</h2>
  <div class="grid clearfix">
    <div class="col col3 field">
      <label>Limite de Crédito (R$)</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ ($customer->credit_limit ?? null) ? number_format($customer->credit_limit, 2, ',', '.') : '—' }}</div> @endif
    </div>
    <div class="col col3 field">
      <label>Tabela de Preço</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ ($customer->priceList ?? null)?->name }}</div> @endif
    </div>
    <div class="col col3 field">
      <label>Situação</label>
      @if($blank ?? false) <div class="blank"></div>
      @else <div class="value">{{ $customer->situation ?? 'Ativo' }}</div> @endif
    </div>
  </div>

  {{-- 8. Assinatura --}}
  <div class="signatures clearfix">
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-label">Assinatura do Cliente</div>
      @if(!($blank ?? false) && ($customer->document ?? null))
        <div style="font-size:8px;margin-top:2px;">CPF: {{ $customer->document }}</div>
      @else
        <div style="font-size:8px;margin-top:2px;">CPF: ____________________</div>
      @endif
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-label">Responsável pelo Cadastro</div>
      <div style="font-size:8px;margin-top:2px;">Data: {{ $date }}</div>
    </div>
    <div class="sig-box">
      <div class="sig-line"></div>
      <div class="sig-label">Aprovação Gerência</div>
      <div style="font-size:8px;margin-top:2px;">&nbsp;</div>
    </div>
  </div>

  {{-- LGPD --}}
  <div class="lgpd">
    <strong>Autorização LGPD:</strong>
    Declaro que autorizo o uso dos meus dados pessoais fornecidos neste cadastro para fins comerciais,
    de cobrança e de relacionamento com {{ $tenant?->name ?? 'esta empresa' }}, nos termos da
    Lei Geral de Proteção de Dados (Lei 13.709/2018). Os dados não serão compartilhados com terceiros
    sem meu consentimento expresso.
  </div>

  <button class="print-btn" onclick="window.print()">Imprimir / Salvar PDF</button>
</div>
</body>
</html>
