<?php

return [
  'dashboard' => 
  [
  ],
  'common' => 
  [
    'created_at' => 'Criado em',
    'updated_at' => 'Atualizado em',
    'expiring_at' => 'Expira em',
    'canceled_at' => 'Cancelado em',
    'create' => 'Criar',
    'edit' => 'Atualizar',
    'delete' => 'Excluir',
    'view' => 'Visualizar',
    'id' => 'ID',
    'files' => 'Arquivos',
  ],
  'navigation' => 
  [
    'dashboard' => 'Painel',
    'groups' => 
    [
      'users' => 'Usuários',
      'posts' => 'Posts',
      'finances' => 'Finanças',
      'taxes' => 'Impostos',
      'stories' => 'Conteúdo curto',
      'streams' => 'Transmissões',
      'site' => 'Site',
      'settings' => 'Configurações',
    ],
  ],
  'filters' => 
  [
    'title' => 'Filtros',
    'start_date' => 'Data inicial',
    'end_date' => 'Data final',
    'today' => 'Hoje',
    'week' => 'Última semana',
    'month' => 'Último mês',
    'year' => 'Este ano',
    'last_month' => 'Últimos 30 dias',
    'last_year' => 'Últimos 12 meses',
  ],
  'widgets' => 
  [
    'stats_overview' => 
    [
      'title' => 'Visão geral dos últimos 7 dias',
      'revenue' => 
      [
        'label' => 'Receita',
        'description' => 'Receita total gerada',
      ],
      'new_users' => 
      [
        'label' => 'Novos usuários',
        'description' => 'Usuários cadastrados',
      ],
      'new_payments' => 
      [
        'label' => 'Pagamentos',
        'description' => 'Transações concluídas',
      ],
    ],
    'users_chart' => 
    [
      'title' => 'Usuários',
      'datasets' => 
      [
        'users' => 'Usuários',
        'user_messages' => 'Mensagens de usuários',
      ],
    ],
    'posts_chart' => 
    [
      'title' => 'Posts',
      'filters' => 
      [
        'today' => 'Hoje',
        'week' => 'Última semana',
        'month' => 'Último mês',
        'year' => 'Este ano',
      ],
      'datasets' => 
      [
        'posts' => 'Posts',
        'comments' => 'Comentários',
        'reactions' => 'Reações',
      ],
    ],
    'transactions_chart' => 
    [
      'title' => 'Pagamentos',
      'filters' => 
      [
        'today' => 'Hoje',
        'week' => 'Última semana',
        'month' => 'Último mês',
        'year' => 'Este ano',
      ],
      'datasets' => 
      [
        'transactions' => 'Pagamentos',
        'subscriptions' => 'Assinaturas',
      ],
    ],
    'streams_chart' => 
    [
      'title' => 'Transmissões',
      'filters' => 
      [
        'today' => 'Hoje',
        'week' => 'Última semana',
        'month' => 'Último mês',
        'year' => 'Este ano',
      ],
      'datasets' => 
      [
        'streams' => 'Transmissões',
        'stream_messages' => 'Mensagens da transmissão',
      ],
    ],
    'product_info' => 
    [
      'title' => 'Guia rápido',
      'website' => 
      [
        'title' => 'Site',
        'description' => 'Visite a página oficial do produto',
      ],
      'documentation' => 
      [
        'title' => 'Documentação',
        'description' => 'Visite a documentação oficial do produto',
      ],
      'changelog' => 
      [
        'title' => 'Registro de alterações',
        'description' => 'Visite o changelog oficial do produto',
      ],
    ],
    'transaction_stats' => 
    [
      'heading' => 'Pagamentos deste ano',
      'total' => 'Total de pagamentos',
      'completed' => 'Pagamentos concluídos',
      'average' => 'Preço médio',
    ],
    'subscription_stats' => 
    [
      'heading' => 'Assinaturas deste ano',
      'total' => 'Total de assinaturas',
      'active' => 'Assinaturas ativas atualmente',
      'average_price' => 'Preço médio',
    ],
  ],
  'resources' => 
  [
    'user' => 
    [
      'label' => 'Usuário',
      'plural' => 'Usuários',
      'sections' => 
      [
        'account_info' => 'Informações da conta',
        'paywall_info' => 'Informações do paywall',
        'profile_info' => 'Informações do perfil',
        'withdrawals_info' => 'Informações de saques',
        'security_info' => 'Informações de segurança',
        'billing_info' => 'Informações de cobrança',
      ],
      'fields' => 
      [
        'id' => 'ID',
        'name' => 'Nome',
        'email' => 'E-mail',
        'username' => 'Nome de usuário',
        'password' => 'Senha',
        'roles' => 'Função',
        'email_verified_at' => 'E-mail verificado em',
        'identity_verified_at' => 'Documento verificado em',
        'birthdate' => 'Data de nascimento',
        'paid_profile' => 'Perfil pago',
        'public_profile' => 'Perfil público',
        'open_profile' => 'Perfil aberto',
        'profile_access_price' => 'Preço de acesso',
        'profile_access_price_3_months' => 'Preço de acesso por 3 meses',
        'profile_access_price_6_months' => 'Preço de acesso por 6 meses',
        'profile_access_price_12_months' => 'Preço de acesso por 12 meses',
        'current_avatar' => 'Avatar atual',
        'avatar' => 'Avatar',
        'current_cover' => 'Capa atual',
        'cover' => 'Capa',
        'bio' => 'Bio',
        'location' => 'Localização',
        'gender_id' => 'Gênero',
        'gender_pronoun' => 'Pronome',
        'website' => 'Site',
        'referral_code' => 'Código de indicação',
        'stripe_account_id' => 'ID do Stripe Connect',
        'country_id' => 'País do Stripe Connect',
        'stripe_onboarding_verified' => 'Onboarding da Stripe verificado',
        'last_ip' => 'Último IP',
        'last_active_at' => 'Última atividade em',
        'enable_geoblocking' => 'Ativar bloqueio geográfico',
        'enable_2fa' => 'Ativar 2FA',
        'billing_address' => 'Endereço de cobrança',
        'first_name' => 'Nome',
        'last_name' => 'Sobrenome',
        'city' => 'Cidade',
        'country' => 'País',
        'state' => 'Estado',
        'postcode' => 'CEP',
        'gender' => 'Gênero',
      ],
      'actions' => 
      [
        'impersonate' => 'Personificar',
        'profile_url' => 'URL do perfil',
      ],
    ],
    'user_verify' => 
    [
      'label' => 'Verificação de identidade',
      'plural' => 'Verificações de identidade',
      'sections' => 
      [
        'verification_details' => 'Detalhes da verificação',
        'verification_details_descr' => 'Gerencie a solicitação de verificação do usuário.',
      ],
      'tabs' => 
      [
        'all' => 'Todos',
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Recusado',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'status' => 'Status',
        'rejectionReason' => 'Motivo da rejeição',
        'files' => 'Prévia dos arquivos',
      ],
      'actions' => 
      [
        'profile_url' => 'URL do perfil',
      ],
      'navigation_badge_tooltip' => 'Número de verificações de identidade pendentes',
    ],
    'release_form' => 
    [
      'label' => 'Autorização de uso de imagem',
      'plural' => 'Autorizações de uso de imagem',
      'sections' => 
      [
        'release_form_details' => 'Detalhes da autorização',
      ],
      'tabs' => 
      [
        'all' => 'Todos',
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
      ],
      'status_labels' => 
      [
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
      ],
      'fields' => 
      [
        'user_id' => 'Criador',
        'title' => 'Título',
        'status' => 'Status',
        'files' => 'Arquivos',
        'reviewed_by' => 'Revisado por',
        'reviewed_at' => 'Revisado em',
        'notes' => 'Observações do criador',
        'rejection_reason' => 'Motivo da rejeição',
      ],
      'actions' => 
      [
        'approve' => 'Aprovar',
        'reject' => 'Rejeitar',
      ],
      'navigation_badge_tooltip' => 'Número de autorizações pendentes',
    ],
    'wallet' => 
    [
      'label' => 'Carteira',
      'plural' => 'Carteiras',
      'sections' => 
      [
        'wallet_details' => 'Detalhes da carteira',
      ],
      'fields' => 
      [
        'id' => 'ID da carteira',
        'user_id' => 'Usuário',
        'total' => 'Valor total',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
      ],
      'helper_texts' => 
      [
        'id' => 'Formato UUID recomendado.',
      ],
    ],
    'notification' => 
    [
      'label' => 'Notificação',
      'plural' => 'Notificações',
      'sections' => 
      [
        'general_info' => 'Informações gerais',
        'notification_details' => 'Detalhes da notificação',
        'linked_models' => 'Modelos vinculados',
      ],
      'fields' => 
      [
        'id' => 'ID da notificação',
        'from_user_id' => 'Do usuário',
        'to_user_id' => 'Para o usuário',
        'type' => 'Tipo de notificação',
        'read' => 'Marcar como lida',
        'post_id' => 'ID do post',
        'post_comment_id' => 'ID do comentário do post',
        'subscription_id' => 'ID da assinatura',
        'transaction_id' => 'ID da transação',
        'reaction_id' => 'ID da reação',
        'withdrawal_id' => 'ID do saque',
        'user_message_id' => 'ID da mensagem do usuário',
        'stream_id' => 'ID da transmissão',
      ],
      'helper_texts' => 
      [
        'id' => 'Formato UUID recomendado.',
        'read' => 'Indica se o usuário viu a notificação.',
      ],
      'types' => 
      [
        'ppv_unlock' => 'Conteúdo desbloqueado',
        'expiring_stream' => 'Transmissão expirando',
        'new_message' => 'Nova mensagem',
        'withdrawal_action' => 'Atualização de saque',
        'new_subscription' => 'Nova assinatura',
        'new_comment' => 'Novo comentário',
        'new_reaction' => 'Nova reação',
        'new_tip' => 'Nova gorjeta',
        'mention' => 'Menção',
      ],
    ],
    'user_message' => 
    [
      'label' => 'Mensagem',
      'plural' => 'Mensagens',
      'sections' => 
      [
        'user_message_details' => 'Detalhes da mensagem do usuário',
        'user_message_details_descr' => 'Gerencie mensagens diretas entre usuários.',
      ],
      'fields' => 
      [
        'sender_id' => 'Remetente',
        'receiver_id' => 'Destinatário',
        'message' => 'Conteúdo da mensagem',
        'price' => 'Preço (opcional)',
        'replyTo' => 'Responder à mensagem ID',
        'isSeen' => 'Foi vista',
        'story_id' => 'Story',
      ],
      'attachments' => 
      [
        'title' => 'Ver anexos de :name',
        'breadcrumb' => 'Anexos',
        'nav_label' => 'Ver anexos',
        'file_link' => 'Abrir arquivo',
        'actions' => 
        [
          'create' => 'Adicionar novo anexo',
        ],
      ],
      'transactions' => 
      [
        'title' => 'Ver pagamentos de :record',
        'breadcrumb' => 'Pagamentos',
        'nav_label' => 'Ver pagamentos',
        'fields' => 
        [
          'id' => 'ID',
          'sender' => 'Remetente',
          'payer' => 'Pagador',
          'status' => 'Status',
          'type' => 'Tipo',
          'payment_provider' => 'Provedor',
          'amount' => 'Valor',
        ],
        'actions' => 
        [
          'create' => 'Adicionar nova transação',
        ],
      ],
    ],
    'reaction' => 
    [
      'label' => 'Reação',
      'plural' => 'Reações',
      'sections' => 
      [
        'reaction_info' => 'Informações da reação',
        'reaction_info_descr' => 'Detalhes sobre o usuário e o tipo de reação.',
        'target_content' => 'Conteúdo de destino',
        'target_content_descr' => 'Especifique o conteúdo ao qual esta reação está anexada.',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'reaction_type' => 'Tipo de reação',
        'post_id' => 'ID do post',
        'post_comment_id' => 'ID do comentário',
      ],
      'types' => 
      [
        'like' => 'Curtir',
      ],
    ],
    'user_list' => 
    [
      'label' => 'Lista',
      'plural' => 'Listas',
      'sections' => 
      [
        'list_details' => 'Detalhes da lista',
        'list_details_descr' => 'Forneça um nome e um tipo para esta lista de usuários.',
        'owner' => 'Proprietário',
        'owner_descr' => 'Selecione o usuário dono desta lista.',
      ],
      'fields' => 
      [
        'name' => 'Nome da lista',
        'type' => 'Tipo de lista',
        'user_id' => 'Dono da lista',
      ],
      'placeholders' => 
      [
        'name' => 'Digite o nome da lista',
      ],
      'types' => 
      [
        'blocked' => 'Usuários bloqueados',
        'following' => 'Seguindo',
        'followers' => 'Seguidores',
        'custom' => 'Lista personalizada',
      ],
      'members' => 
      [
        'title' => 'Ver membros de :name',
        'breadcrumb' => 'Membros',
        'navigation_label' => 'Ver membros',
        'fields' => 
        [
          'id' => 'ID',
          'username' => 'Usuário',
          'created_at' => 'Criado em',
        ],
      ],
    ],
    'user_list_member' => 
    [
      'label' => 'Membro da lista',
      'plural' => 'Membros da lista',
      'actions' => 
      [
        'create' => 'Adicionar novo membro',
      ],
      'sections' => 
      [
        'list_association' => 'Associação da lista',
        'list_association_descr' => 'Atribua um usuário a uma lista específica.',
      ],
      'fields' => 
      [
        'list_id' => 'ID da lista de usuários',
        'user_id' => 'Usuário',
      ],
      'placeholders' => 
      [
        'list_id' => 'Selecione uma lista',
        'user_id' => 'Selecione um usuário',
      ],
    ],
    'user_bookmark' => 
    [
      'label' => 'Favorito',
      'plural' => 'Favoritos',
      'sections' => 
      [
        'bookmark_details' => 'Detalhes do favorito',
        'bookmark_details_descr' => 'Vincule um usuário a um post salvo.',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'post_id' => 'ID do post',
        'reel_id' => 'ID do reel',
        'username' => 'Usuário',
      ],
    ],
    'user_report' => 
    [
      'label' => 'Denúncia',
      'plural' => 'Denúncias',
      'sections' => 
      [
        'reporter_reported' => 'Usuários denunciantes e denunciados',
        'reporter_reported_descr' => 'Identifique o usuário que enviou a denúncia e o usuário denunciado.',
        'reported_content' => 'Conteúdo denunciado (opcional)',
        'reported_content_descr' => 'Vincule esta denúncia a um conteúdo específico.',
        'report_details' => 'Detalhes da denúncia',
      ],
      'tabs' => 
      [
        'all' => 'Todos',
        'received' => 'Recebido',
        'seen' => 'Visto',
        'solved' => 'Resolvido',
      ],
      'fields' => 
      [
        'from_user_id' => 'Denunciante',
        'user_id' => 'Usuário denunciado',
        'post_id' => 'ID do post',
        'message_id' => 'ID da mensagem',
        'stream_id' => 'ID da transmissão',
        'type' => 'Motivo da denúncia',
        'status' => 'Status',
        'details' => 'Detalhes adicionais',
        'story_id' => 'ID do story',
        'reel_id' => 'ID do reel',
        'reel_comment_id' => 'ID do comentário do reel',
      ],
      'types' => 
      [
        'i_dont_like' => 'Não gosto disso',
        'spam' => 'Spam',
        'dmca' => 'DMCA',
        'offensive_content' => 'Conteúdo ofensivo',
        'abuse' => 'Abuso',
      ],
      'statuses' => 
      [
        'received' => 'Recebido',
        'seen' => 'Visto',
        'solved' => 'Resolvido',
      ],
      'actions' => 
      [
        'view_admin' => 'Ver página admin',
        'view_public' => 'Ver página pública',
      ],
      'navigation_badge_tooltip' => 'Número de denúncias pendentes',
    ],
    'featured_user' => 
    [
      'label' => 'Usuário em destaque',
      'plural' => 'Usuários em destaque',
      'sections' => 
      [
        'main' => 'Destacar um usuário',
        'main_descr' => 'Selecione um usuário para destacar na plataforma.',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário em destaque',
        'username' => 'Nome de usuário',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
      ],
    ],
    'user_tax' => 
    [
      'label' => 'Informações fiscais',
      'plural' => 'Informações fiscais',
      'sections' => 
      [
        'user' => 'Associação do usuário',
        'user_descr' => 'Vincule as informações fiscais a um usuário e ao país emissor.',
        'tax' => 'Identificação fiscal',
        'tax_descr' => 'Detalhes legais e de identificação fiscal.',
        'personal' => 'Dados pessoais',
        'personal_descr' => 'Informações pessoais e de endereço adicionais.',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'issuing_country_id' => 'País emissor',
        'legal_name' => 'Nome legal',
        'tax_identification_number' => 'Número de identificação fiscal',
        'vat_number' => 'Número de VAT',
        'tax_type' => 'Tipo de imposto',
        'date_of_birth' => 'Data de nascimento',
        'primary_address' => 'Endereço principal',
        'earnings_ytd' => 'Ganhos no ano (bruto)',
      ],
      'filters' => 
      [
        'min_earnings' => 'Ganhos mínimos',
      ],
      'descriptions' => 
      [
        'primary_address' => 'Digite o endereço completo',
      ],
      'placeholders' => 
      [
        'user_id' => 'Selecionar usuário',
        'issuing_country_id' => 'Selecione o país',
      ],
      'options' => 
      [
        'types' => 
        [
          'dac7' => 'DAC7',
        ],
      ],
    ],
    'post_comment' => 
    [
      'label' => 'Comentário',
      'plural' => 'Comentários',
      'sections' => 
      [
        'post_comment_details' => 'Detalhes do comentário do post',
        'post_comment_details_descr' => 'Detalhes do comentário do post.',
      ],
      'fields' => 
      [
        'id' => 'ID',
        'author' => 'Usuário',
        'message' => 'Mensagem',
        'post_id' => 'Post',
      ],
    ],
    'attachment' => 
    [
      'label' => 'Anexo',
      'plural' => 'Anexos',
      'sections' => 
      [
        'file_and_metadata' => 'Arquivo e metadados',
        'associations' => 'Associações',
        'attachment_details' => 'Detalhes do anexo',
        'attachment_details_descr' => 'Configure ou revise os detalhes do anexo.',
      ],
      'fields' => 
      [
        'id' => 'ID',
        'filename' => 'Nome do arquivo',
        'file' => 'Arquivo',
        'driver' => 'Driver de armazenamento',
        'type' => 'Tipo',
        'user_id' => 'Usuário',
        'post_id' => 'ID do post',
        'message_id' => 'ID da mensagem',
        'payment_request_id' => 'ID da solicitação de pagamento',
        'coconut_id' => 'ID do Coconut',
        'has_thumbnail' => 'Tem miniatura',
        'has_blurred_preview' => 'Tem prévia desfocada',
        'open' => 'Abrir arquivo',
        'story_id' => 'Story',
        'reel_id' => 'Reel',
        'sound_id' => 'Som',
        'length' => 'Duração',
      ],
      'help' => 
      [
        'id' => 'Formato UUID recomendado.',
        'driver' => 'Selecione qual driver de armazenamento usar para os arquivos dos usuários.',
        'length' => 'Duração do arquivo de mídia em segundos.',
      ],
    ],
    'poll' => 
    [
      'label' => 'Enquete',
      'plural' => 'Enquetes',
      'sections' => 
      [
        'post_details' => 'Detalhes da enquete',
        'post_details_descr' => 'Configure os detalhes da enquete.',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'post_id' => 'ID do post',
        'ends_at' => 'Termina em',
        'answer_id' => 'Resposta selecionada',
        'answer' => 'Opção',
        'id' => 'ID',
      ],
      'filters' => 
      [
        'poll.id' => 'ID da enquete',
        'user.username' => 'Nome de usuário',
      ],
      'poll_answers' => 
      [
        'poll_choices' => 'Opções da enquete',
        'choices' => 'Opções',
        'actions' => 
        [
          'create' => 'Adicionar nova opção',
          'edit' => 'Editar opção',
          'delete' => 'Excluir opção',
        ],
      ],
      'user_poll_answers' => 
      [
        'label' => 'Respostas dos usuários',
        'fields' => 
        [
          'user_id' => 'Usuário',
          'answer_id' => 'Resposta selecionada',
          'answer' => 'Resposta',
        ],
        'actions' => 
        [
          'create' => 'Adicionar resposta',
          'edit' => 'Editar resposta',
          'delete' => 'Excluir resposta',
        ],
      ],
    ],
    'transaction' => 
    [
      'label' => 'Transação',
      'plural' => 'Transações',
      'sections' => 
      [
        'participants' => 'Participantes',
        'participants_descr' => 'Defina o remetente e o destinatário envolvidos na transação.',
        'details' => 'Detalhes da transação',
        'details_descr' => 'Defina o status, tipo, provedor e dados principais.',
        'related' => 'Entidades relacionadas',
        'related_descr' => 'Associe esta transação a conteúdos ou assinaturas.',
        'provider_info' => 'Informações específicas do provedor',
        'provider_info_descr' => 'Adicione IDs ou tokens opcionais de provedores externos.',
      ],
      'fields' => 
      [
        'sender_user_id' => 'Comprador',
        'recipient_user_id' => 'Vendedor',
        'status' => 'Status',
        'type' => 'Tipo',
        'payment_provider' => 'Provedor de pagamento',
        'currency' => 'Código da moeda',
        'amount' => 'Valor',
        'taxes' => 'Impostos',
        'subscription_id' => 'Assinatura',
        'post_id' => 'Post',
        'stream_id' => 'Transmissão',
        'invoice_id' => 'Fatura',
        'user_message_id' => 'Mensagem',
        'paypal_payer_id' => 'ID do pagador PayPal',
        'paypal_transaction_id' => 'ID da transação PayPal',
        'paypal_transaction_token' => 'Token da transação PayPal',
        'stripe_transaction_id' => 'ID da transação Stripe',
        'stripe_session_id' => 'ID da sessão Stripe',
        'coinbase_charge_id' => 'ID da cobrança Coinbase',
        'coinbase_transaction_token' => 'Token da transação Coinbase',
        'nowpayments_payment_id' => 'ID do pagamento NowPayments',
        'nowpayments_order_id' => 'ID do pedido NowPayments',
        'ccbill_transaction_token' => 'Token da transação CCBill',
        'ccbill_transaction_id' => 'ID da transação CCBill',
        'ccbill_subscription_id' => 'ID da assinatura CCBill',
        'verotel_payment_token' => 'Token da transação Verotel',
        'verotel_sale_id' => 'ID da venda Verotel',
        'paystack_payment_token' => 'Token de pagamento Paystack',
        'mercado_payment_token' => 'Token de pagamento Mercado Pago',
        'mercado_payment_id' => 'ID do pagamento Mercado Pago',
        'yookassa_payment_id' => 'ID do pagamento YooMoney',
        'yookassa_payment_token' => 'Token de pagamento YooMoney',
        'mollie_payment_id' => 'ID do pagamento Mollie',
        'mollie_payment_token' => 'Token de pagamento Mollie',
        'flutterwave_payment_id' => 'ID do pagamento Flutterwave',
        'flutterwave_payment_token' => 'Token de pagamento Flutterwave',
        'coingate_order_id' => 'ID do pedido CoinGate',
        'coingate_payment_token' => 'Token de callback CoinGate',
        'xendit_payment_id' => 'ID da sessão de pagamento Xendit',
        'xendit_payment_token' => 'Token de pagamento Xendit',
        'paddle_transaction_id' => 'ID da transação Paddle',
        'paddle_transaction_token' => 'Token da transação Paddle',
        'cryptocom_payment_id' => 'ID do pagamento Crypto.com',
        'cryptocom_payment_token' => 'Token de pagamento Crypto.com',
        'sender' => 'Remetente',
        'receiver' => 'Destinatário',
        'receiver_user_id' => 'Vendedor',
        'id' => 'ID',
      ],
      'helpers' => 
      [
        'taxes' => 'JSON obrigatório. Exemplos podem ser obtidos de transações criadas pelo app.',
        'taxes_placeholder' => 'Insira a discriminação de impostos ou observações',
      ],
      'status_labels' => 
      [
        'pending' => 'Pendente',
        'refunded' => 'Reembolsado',
        'partially_paid' => 'Pago parcialmente',
        'declined' => 'Recusado',
        'initiated' => 'Iniciado',
        'canceled' => 'Cancelado',
        'approved' => 'Aprovado',
      ],
      'type_labels' => 
      [
        'tip' => 'Gorjeta',
        'deposit' => 'Depósito',
        'withdrawal' => 'Saque',
        'chat_tip' => 'Gorjeta no chat',
        'stream_access' => 'Acesso à transmissão',
        'message_unlock' => 'Desbloqueio de mensagem',
        'post_unlock' => 'Desbloqueio de post',
        'one_month_subscription' => 'Assinatura de 1 mês',
        'three_months_subscription' => 'Assinatura de 3 meses',
        'six_months_subscription' => 'Assinatura de 6 meses',
        'yearly_subscription' => 'Assinatura anual',
        'subscription_renewal' => 'Renovação da assinatura',
      ],
      'tabs' => 
      [
        'all' => 'Todos',
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'declined' => 'Recusado',
      ],
    ],
    'post' => 
    [
      'label' => 'Post',
      'plural' => 'Posts',
      'sections' => 
      [
        'details' => 'Detalhes do post',
        'details_descr' => 'Configure os detalhes do post.',
        'settings' => 'Configurações do post',
        'settings_descr' => 'Configurações de preço, status e tempo.',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'text' => 'Texto do post',
        'price' => 'Preço',
        'status' => 'Status',
        'release_date' => 'Data de publicação',
        'expire_date' => 'Data de expiração',
        'is_pinned' => 'Fixar este post',
      ],
      'actions' => 
      [
        'post_url' => 'URL do post',
      ],
      'status_labels' => 
      [
        0 => 'Pendente',
        1 => 'Aprovado',
        2 => 'Rejeitado',
      ],
    ],
    'hashtag' => 
    [
      'label' => 'Hashtag',
      'plural' => 'Hashtags',
      'sections' => 
      [
        'hashtag_info' => 'Informações da hashtag',
        'hashtag_info_descr' => 'Crie e gerencie hashtags usadas em posts e comentários.',
      ],
      'fields' => 
      [
        'tag' => 'Tag',
        'tag_helper' => 'Digite a hashtag sem o “#”. Apenas letras, números e sublinhados são permitidos (máx. 64). Será salva em minúsculas.',
      ],
    ],
    'hashtag_link' => 
    [
      'label' => 'Link da hashtag',
      'plural' => 'Links de hashtag',
      'fields' => 
      [
        'post_id' => 'ID do post',
        'post_comment_id' => 'ID do comentário',
      ],
    ],
    'subscription' => 
    [
      'label' => 'Assinatura',
      'plural' => 'Assinaturas',
      'sections' => 
      [
        'user_info' => 'Informações do usuário',
        'subscription_details' => 'Detalhes da assinatura',
        'platform_identifiers' => 'Identificadores da plataforma',
        'timestamps' => 'Registros de data/hora',
      ],
      'fields' => 
      [
        'sender_user_id' => 'Assinante',
        'recipient_user_id' => 'Criador',
        'subscriber.username' => 'Assinante',
        'creator.username' => 'Criador',
        'type' => 'Tipo',
        'status' => 'Status',
        'provider' => 'Provedor de pagamento',
        'amount' => 'Valor',
        'paypal_agreement_id' => 'ID do acordo PayPal',
        'paypal_plan_id' => 'ID do plano PayPal',
        'stripe_subscription_id' => 'ID da assinatura Stripe',
        'ccbill_subscription_id' => 'ID da assinatura CCBill',
        'verotel_sale_id' => 'ID da venda Verotel',
        'expires_at' => 'Expira em',
        'canceled_at' => 'Cancelado em',
      ],
      'status_labels' => 
      [
        'active' => 'Ativo',
        'completed' => 'Concluído',
        'canceled' => 'Cancelado',
        'suspended' => 'Suspenso',
        'expired' => 'Expirado',
        'failed' => 'Falhou',
        'pending' => 'Pendente',
      ],
      'tabs' => 
      [
        'all' => 'Todos',
        'pending' => 'Pendente',
        'active' => 'Ativo',
        'canceled' => 'Cancelado',
      ],
      'type_labels' => 
      [
        'one_month_subscription' => 'Assinatura de 1 mês',
        'three_months_subscription' => 'Assinatura de 3 meses',
        'six_months_subscription' => 'Assinatura de 6 meses',
        'yearly_subscription' => 'Assinatura de 1 ano',
      ],
    ],
    'withdrawal' => 
    [
      'label' => 'Saque',
      'plural' => 'Saques',
      'sections' => 
      [
        'details' => 'Detalhes do saque',
        'details_descr' => 'Configure ou revise os detalhes da solicitação de saque.',
        'payout_summary' => 'Resumo do pagamento',
        'payout_details' => 'Detalhes do pagamento',
      ],
      'fields' => 
      [
        'id' => 'ID',
        'username' => 'Usuário',
        'amount' => 'Valor',
        'requested_amount' => 'Valor solicitado',
        'fee' => 'Taxa',
        'net_payout' => 'Pagamento líquido',
        'status' => 'Status',
        'processed' => 'Processado',
        'payment_method' => 'Método de pagamento',
        'payout_method_key' => 'Chave do método de pagamento',
        'payment_identifier' => 'Identificador do pagamento',
        'stripe_payout_id' => 'ID do pagamento Stripe',
        'stripe_transfer_id' => 'ID da transferência Stripe',
        'user_id' => 'Usuário',
        'message' => 'Mensagem',
        'notes' => 'Observações',
        'details_label' => 'Detalhes',
        'iban' => 'IBAN',
        'paypal_email' => 'E-mail do PayPal',
        'wallet_address' => 'Endereço da carteira',
        'payout_destination' => 'Destino do pagamento',
        'stripe_account' => 'Conta Stripe',
        'account_label' => 'Rótulo da conta',
        'account_holder' => 'Titular da conta',
        'swift_bic' => 'SWIFT/BIC',
        'bank' => 'Banco',
        'bank_address' => 'Endereço do banco',
        'country' => 'País',
        'method' => 'Método',
      ],
      'helpers' => 
      [
        'stripe_connect_warning' => 'Saques usando Stripe Connect só podem ser criados por criadores',
        'status_creation_rule' => 'Um novo saque deve ser criado com o status solicitado.',
        'processed_warning' => 'Esta solicitação de saque já foi processada',
        'amount_overflow' => 'O saldo de créditos deste usuário é menor que o valor do saque. Tente um valor menor',
        'fees_info' => 'As taxas são calculadas automaticamente, se as taxas de saque estiverem ativadas nas configurações de pagamento.',
        'summary_empty' => 'O resumo do pagamento aparecerá após o saque ser criado.',
        'payout_details_empty' => 'Não há dados de pagamento salvos neste saque.',
        'stored_notes' => 'Observações do usuário salvas e enviadas com este saque.',
        'stored_method_reference' => 'Método de saque salvo apenas para referência.',
        'stored_payout_reference' => 'Destino de pagamento salvo apenas para referência.',
        'stored_payout_used' => 'Destino de pagamento salvo usado para este saque.',
        'stripe_payout_reference' => 'Referência de pagamento gerada pela Stripe.',
        'stripe_transfer_reference' => 'Referência de transferência gerada pela Stripe.',
        'processed_flag' => 'Marca este saque como já tratado. Geralmente é definido automaticamente quando o saque é aprovado ou rejeitado.',
      ],
      'status_labels' => 
      [
        'approved' => 'Aprovado',
        'requested' => 'Solicitado',
        'rejected' => 'Rejeitado',
      ],
      'actions' => 
      [
        'approve' => 'Aprovar',
        'reject' => 'Rejeitar',
      ],
      'tabs' => 
      [
        'all' => 'Todos',
        'requested' => 'Solicitado',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
      ],
      'export' => 
      [
        'csv' => 'CSV de pagamentos',
        'gross' => 'Bruto',
        'net' => 'Líquido',
        'method' => 'Método',
        'identifier' => 'Identificador',
        'saved_account' => 'Conta salva',
        'payout_details' => 'Detalhes do pagamento',
        'yes' => 'Sim',
        'no' => 'Não',
      ],
      'navigation_badge_tooltip' => 'Número de saques pendentes',
    ],
    'payment_request' => 
    [
      'label' => 'Solicitação de pagamento',
      'plural' => 'Solicitações de pagamento',
      'sections' => 
      [
        'payment_request' => 'Solicitação de pagamento',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'transaction_id' => 'ID da transação',
        'amount' => 'Valor',
        'status' => 'Status',
        'type' => 'Tipo',
        'reason' => 'Motivo da rejeição',
        'message' => 'Mensagem',
      ],
      'status_labels' => 
      [
        'approved' => 'Aprovado',
        'pending' => 'Pendente',
        'rejected' => 'Rejeitado',
      ],
      'type_labels' => 
      [
        'deposit' => 'Depósito',
      ],
      'tabs' => 
      [
        'all' => 'Todos',
        'pending' => 'Pendente',
        'approved' => 'Aprovado',
        'rejected' => 'Rejeitado',
      ],
    ],
    'invoice' => 
    [
      'label' => 'Fatura',
      'plural' => 'Faturas',
      'sections' => 
      [
        'invoice_info' => 'Informações da fatura',
        'invoice_info_descr' => 'Aqui você pode ver os dados codificados de uma fatura gerada.',
      ],
      'fields' => 
      [
        'invoice_id' => 'ID da fatura',
        'transaction_id' => 'ID da transação',
        'data' => 'Dados',
      ],
      'actions' => 
      [
        'invoice_url' => 'URL da fatura',
      ],
    ],
    'tax' => 
    [
      'label' => 'Imposto',
      'plural' => 'Impostos',
      'sections' => 
      [
        'details' => 'Detalhes do imposto',
        'details_descr' => 'Edite os detalhes das taxas do seu site.',
      ],
      'fields' => 
      [
        'name' => 'Nome',
        'type' => 'Tipo',
        'percentage' => 'Valor',
        'country_name' => 'País',
        'countries_name' => 'Países',
        'hidden' => 'Oculto',
      ],
      'type_labels' => 
      [
        'fixed' => 'Fixo',
        'exclusive' => 'Exclusivo',
        'inclusive' => 'Inclusivo',
      ],
    ],
    'country' => 
    [
      'label' => 'País',
      'plural' => 'Países',
      'sections' => 
      [
        'country_details' => 'Detalhes do país',
        'country_details_descr' => 'Detalhes do país/região.',
      ],
      'fields' => 
      [
        'name' => 'Nome',
        'country_code' => 'Código do país',
        'phone_code' => 'Código telefônico',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
      ],
    ],
    'stream' => 
    [
      'label' => 'Transmissão',
      'plural' => 'Transmissões',
      'sections' => 
      [
        'stream_details' => 'Detalhes da transmissão',
        'stream_details_descr' => 'Detalhes básicos sobre a transmissão.',
        'stream_source' => 'Fonte e reprodução da transmissão',
        'stream_source_descr' => 'Configuração para entrega da transmissão e RTMP.',
        'advanced_metadata' => 'Avançado e metadados',
      ],
      'fields' => 
      [
        'name' => 'Nome da transmissão',
        'slug' => 'Slug',
        'price' => 'Preço de acesso',
        'user_id' => 'Usuário',
        'poster' => 'Imagem do pôster',
        'status' => 'Status',
        'requires_subscription' => 'Requer assinatura',
        'is_public' => 'Transmissão pública',
        'sent_expiring_reminder' => 'Lembrete de expiração enviado',
        'driver' => 'Driver de transmissão',
        'pushr_id' => 'ID do Pushr',
        'rtmp_key' => 'Chave RTMP',
        'rtmp_server' => 'Servidor RTMP',
        'hls_link' => 'Link de reprodução HLS',
        'vod_link' => 'Link VOD',
        'settings' => 'Configurações da transmissão (JSON)',
        'ended_at' => 'Encerrada em',
        'created_at' => 'Criado',
        'updated_at' => 'Atualizado',
      ],
      'status_labels' => 
      [
        'all' => 'Todos',
        'in_progress' => 'Em andamento',
        'ended' => 'Encerrada',
        'deleted' => 'Excluído',
      ],
      'driver_labels' => 
      [
        1 => 'PushrCDN',
        2 => 'LiveKit',
      ],
    ],
    'stream_message' => 
    [
      'label' => 'Mensagem da transmissão',
      'plural' => 'Mensagens da transmissão',
      'sections' => 
      [
        'message_details' => 'Detalhes da mensagem',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'stream_id' => 'Transmissão',
        'message' => 'Conteúdo da mensagem',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
      ],
      'help' => 
      [
        'user_id' => 'Selecione o usuário que enviou a mensagem.',
        'stream_id' => 'Escolha a transmissão para associar a esta mensagem.',
        'message' => 'O conteúdo da mensagem do chat.',
      ],
    ],
    'public_page' => 
    [
      'label' => 'Página pública',
      'plural' => 'Páginas públicas',
      'sections' => 
      [
        'page_details' => 'Detalhes da página',
        'page_details_descr' => 'Configure o conteúdo e a estrutura desta página pública.',
        'display_settings' => 'Configurações de exibição',
        'display_settings_descr' => 'Controle como e onde esta página aparece.',
      ],
      'fields' => 
      [
        'title' => 'Título',
        'title_helper' => 'Título da página exibido no cabeçalho e na lista.',
        'short_title' => 'Título curto',
        'short_title_helper' => 'Título alternativo mais curto usado para navegação ou menus.',
        'slug' => 'Slug',
        'content' => 'Conteúdo',
        'slug_helper' => 'Identificador único usado na URL (sem espaços ou caracteres especiais).',
        'shown_in_footer' => 'Exibido no rodapé',
        'shown_in_footer_helper' => 'Ative para mostrar esta página no rodapé do site.',
        'is_tos' => 'Termos de serviço',
        'is_tos_helper' => 'Ative se esta página representa os Termos de Serviço.',
        'is_privacy' => 'Política de privacidade',
        'is_privacy_helper' => 'Ative se esta página representa a Política de Privacidade.',
        'show_last_update_date' => 'Mostrar data da última atualização',
        'show_last_update_date_helper' => 'Se ativado, mostra a data da última modificação na página.',
        'page_order' => 'Ordem da página',
        'page_order_helper' => 'Define a ordem em que esta página aparece nas listagens.',
        'page_url' => 'URL da página',
      ],
    ],
    'contact_message' => 
    [
      'label' => 'Mensagem de contato',
      'plural' => 'Mensagens de contato',
      'fields' => 
      [
        'email' => 'E-mail',
        'subject' => 'Assunto',
        'message' => 'Mensagem',
        'status' => 'Status',
        'is_replied' => 'Respondido',
        'replied_at' => 'Respondido em',
        'replied_by' => 'Respondido por',
        'reply_details' => 'Detalhes da resposta',
        'created_at' => 'Criado em',
        'updated_at' => 'Atualizado em',
      ],
      'status' => 
      [
        'pending' => 'Pendente',
        'replied' => 'Respondido',
        'unknown_replier' => 'admin desconhecido',
      ],
      'helpers' => 
      [
        'is_replied' => 'Use isto após responder pelo seu cliente de e-mail.',
      ],
      'reply_details' => 'Marcado como respondido em :date por :user.',
      'filters' => 
      [
        'reply_status' => 'Status da resposta',
      ],
      'actions' => 
      [
        'mark_replied' => 'Marcar como respondido',
        'mark_unreplied' => 'Marcar como pendente',
      ],
    ],
    'global_announcement' => 
    [
      'label' => 'Anúncio',
      'plural' => 'Anúncios',
      'fields' => 
      [
        'content' => 'Conteúdo',
        'size' => 'Tamanho',
        'expiring_at' => 'Expira em',
        'is_published' => 'Publicado',
        'is_dismissible' => 'Pode ser dispensado',
        'is_sticky' => 'Fixo',
        'is_global' => 'Global',
        'id_verified_only' => 'Apenas com documento verificado',
      ],
      'helpers' => 
      [
        'is_published' => 'Se o anúncio fica visível para os usuários.',
        'is_dismissible' => 'Permite que os usuários fechem ou ocultem este anúncio.',
        'is_sticky' => 'Mantém o anúncio fixado no topo.',
        'is_global' => 'Mostra o anúncio para todos os usuários do sistema.',
        'id_verified_only' => 'Visível apenas para usuários que verificaram o documento.',
      ],
      'sections' => 
      [
        'content' => 'Conteúdo',
        'content_descr' => 'Detalhes do anúncio.',
        'visibility' => 'Visibilidade',
        'visibility_descr' => 'Ative/desative comportamentos de exibição.',
      ],
      'size_labels' => 
      [
        'regular' => 'Regular',
        'small' => 'Pequeno',
      ],
    ],
    'reward' => 
    [
      'label' => 'Indicação',
      'plural' => 'Indicações',
      'sections' => 
      [
        'referral_info' => 'Informações da recompensa por indicação',
        'referral_info_descr' => 'Atribua recompensas geradas por atividade de indicação.',
      ],
      'fields' => 
      [
        'id' => 'ID',
        'from_user_id' => 'Indicador',
        'to_user_id' => 'Usuário indicado',
        'referral_code_usage_id' => 'Uso do código de indicação',
        'amount' => 'Valor da recompensa',
        'transaction_id' => 'ID da transação',
        'reward_type' => 'Tipo de recompensa',
      ],
      'help' => 
      [
        'reward_type' => 'Código do tipo de recompensa.',
      ],
    ],
    'story' => 
    [
      'label' => 'Story',
      'plural' => 'Stories',
      'sections' => 
      [
        'details' => 'Detalhes do story',
        'details_descr' => 'Campos principais do story e propriedade.',
        'settings' => 'Configurações do story',
        'settings_descr' => 'Visibilidade, expiração, links e opções de exibição.',
        'overlay' => 'Sobreposição',
        'overlay_descr' => 'Dados da sobreposição (JSON) usados pelo visualizador (ex.: x/y).',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'mode' => 'Modo',
        'text' => 'Texto',
        'overlay' => 'Sobreposição',
        'bg_preset' => 'Predefinição de fundo',
        'is_public' => 'Público',
        'is_highlight' => 'Destacado',
        'expires_at' => 'Expira em',
        'sound_id' => 'Som',
        'views' => 'Visualizações',
        'link_url' => 'URL do link',
        'link_text' => 'Rótulo do link',
      ],
      'mode_labels' => 
      [
        'media' => 'Foto / Vídeo',
        'text' => 'Texto',
      ],
      'help' => 
      [
        'overlay' => 'Armazenado como JSON (x/y).',
        'sound_id' => 'Opcional: som anexado a este story.',
        'bg_preset' => 'Aplica-se apenas a stories de texto.',
        'link_url' => 'Deve começar com http:// ou https://',
        'link_text' => 'Exibido como rótulo do CTA no visualizador.',
      ],
      'actions' => 
      [
        'view_in_app' => 'Ver no app',
      ],
    ],
    'reel' => 
    [
      'label' => 'Reel',
      'plural' => 'Reels',
      'sections' => 
      [
        'details' => 'Detalhes do reel',
        'details_descr' => 'Campos principais do reel e propriedade.',
        'settings' => 'Configurações do reel',
        'settings_descr' => 'Visibilidade e opções de exibição.',
        'overlay' => 'Sobreposição',
        'overlay_descr' => 'Dados da sobreposição (JSON) usados pelo visualizador.',
      ],
      'fields' => 
      [
        'user_id' => 'Usuário',
        'caption' => 'Legenda',
        'overlay' => 'Sobreposição',
        'is_public' => 'Público',
        'sound_id' => 'Som',
        'views' => 'Visualizações',
        'comments' => 'Comentários',
        'reactions' => 'Reações',
        'bookmarks' => 'Favoritos',
      ],
      'help' => 
      [
        'overlay' => 'Armazenado como JSON.',
        'sound_id' => 'Opcional: som anexado a este reel.',
      ],
      'actions' => 
      [
        'view_in_app' => 'Ver no app',
      ],
    ],
    'reel_comment' => 
    [
      'label' => 'Comentário do reel',
      'plural' => 'Comentários do reel',
      'fields' => 
      [
        'id' => 'ID',
        'user_id' => 'Usuário',
        'parent_id' => 'Comentário principal',
        'message' => 'Mensagem',
        'reactions' => 'Reações',
      ],
    ],
    'sound' => 
    [
      'label' => 'Som',
      'plural' => 'Sons',
      'sections' => 
      [
        'details' => 'Detalhes do som',
        'details_descr' => 'Informações básicas sobre o som.',
        'settings' => 'Configurações',
        'settings_descr' => 'Controles de visibilidade e disponibilidade do som.',
        'media' => 'Mídia',
        'media_descr' => 'Arquivo de áudio e imagem de capa associados a este som.',
      ],
      'fields' => 
      [
        'title' => 'Título',
        'artist' => 'Artista',
        'description' => 'Descrição',
        'is_active' => 'Ativo',
        'cover' => 'Capa',
        'audio' => 'Arquivo de áudio',
        'length' => 'Duração',
        'attachments' => 'Anexos',
      ],
      'help' => 
      [
        'title' => 'Nome exibido do som.',
        'artist' => 'Artista ou autor do som.',
        'description' => 'Descrição opcional para uso administrativo.',
        'is_active' => 'Somente sons ativos podem ser selecionados em stories.',
        'cover' => 'Imagem de capa exibida no seletor de som.',
        'audio' => 'Arquivo de áudio principal associado a este som.',
      ],
      'actions' => 
      [
        'view_attachments' => 'Ver anexos',
      ],
    ],
  ],
  'settings_forms' => 
  [
    'security' => 
    [
      'email_domains' => 
      [
        'tab' => 'Domínios de e-mail',
        'fields' => 
        [
          'domain_policy' => 'Política de domínio',
          'allowedlist_domains' => 'Domínios permitidos',
          'blocklist_domains' => 'Domínios bloqueados',
        ],
        'options' => 
        [
          'allow_all' => 'Permitir todos os domínios',
          'allowlist_only' => 'Permitir apenas domínios da lista permitida',
          'blocklist_only' => 'Bloquear apenas domínios da lista bloqueada',
        ],
        'helpers' => 
        [
          'domain_policy' => 'Controla quais domínios de e-mail podem ser usados no cadastro.',
          'allowedlist_domains' => 'Usado quando a política é “Permitir apenas domínios da lista permitida”. Insira domínios como: example.com (sem esquema).',
          'blocklist_domains' => 'Usado quando a política é “Bloquear apenas domínios da lista bloqueada”. Insira domínios como: bad.com (sem esquema).',
        ],
        'placeholders' => 
        [
          'domains' => 'Adicione um domínio e pressione Enter',
        ],
      ],
      'rate_limits' => 
      [
        'tab' => 'Limites de taxa',
        'fields' => 
        [
          'enable_feature_rate_limits' => 'Ativar limites de taxa dos endpoints',
          'enabled' => 'Ativado',
          'max_attempts' => 'Máximo de tentativas',
          'window' => 'Janela',
        ],
        'helpers' => 
        [
          'enable_feature_rate_limits' => 'Adiciona limites antiabuso configuráveis pelo admin para ações selecionadas de escrita e geração.',
          'enabled' => 'Ative ou desative este limite específico do recurso.',
          'max_attempts' => 'Quantas solicitações são permitidas dentro da janela de tempo.',
          'window' => 'Por quanto tempo contar as tentativas antes de redefinir o limite, em segundos.',
        ],
        'features' => 
        [
          'posts_save' => 
          [
            'title' => 'Salvar posts',
            'description' => 'Aplica-se quando usuários criam ou atualizam posts.',
          ],
          'posts_comments_add' => 
          [
            'title' => 'Adicionar comentários em posts',
            'description' => 'Aplica-se quando usuários adicionam comentários aos posts.',
          ],
          'stories_store' => 
          [
            'title' => 'Publicar stories',
            'description' => 'Aplica-se quando usuários publicam um story.',
          ],
          'reels_store' => 
          [
            'title' => 'Publicar reels',
            'description' => 'Aplica-se quando usuários publicam um reel.',
          ],
          'reels_comments_add' => 
          [
            'title' => 'Adicionar comentários em reels',
            'description' => 'Aplica-se quando usuários adicionam comentários aos reels.',
          ],
          'streams_init' => 
          [
            'title' => 'Iniciar transmissões',
            'description' => 'Aplica-se quando criadores iniciam uma nova transmissão.',
          ],
          'stream_comments_add' => 
          [
            'title' => 'Adicionar comentários na transmissão',
            'description' => 'Aplica-se quando espectadores enviam mensagens no chat da transmissão.',
          ],
          'suggestions_generate' => 
          [
            'title' => 'Gerar sugestões de IA',
            'description' => 'Aplica-se quando usuários geram sugestões com IA.',
          ],
          'profile_asset_generate' => 
          [
            'title' => 'Assets de perfil com IA',
            'description' => 'Aplica-se quando usuários geram imagens de avatar ou capa com IA.',
          ],
          'messenger_send' => 
          [
            'title' => 'Enviar mensagem',
            'description' => 'Aplica-se quando usuários enviam mensagens diretas.',
          ],
        ],
      ],
    ],
    'payments' => 
    [
      'withdrawals' => 
      [
        'fields' => 
        [
          'manual_payout_methods' => 'Métodos manuais de pagamento',
          'custom_withdrawal_message' => 'Mensagem personalizada de saque',
        ],
        'helpers' => 
        [
          'manual_payout_methods' => 'Escolha quais métodos manuais de pagamento os usuários podem solicitar. Os rótulos vêm dos seus arquivos de tradução.',
          'custom_withdrawal_message' => 'Mostra informações adicionais próximas ao formulário de saque para métodos manuais de pagamento.',
        ],
      ],
    ],
  ],
  'settings' => 
  [
    'general' => 'Geral',
    'users' => 'Usuários',
    'feed' => 'Feed',
    'media' => 'Mídia',
    'storage' => 'Armazenamento',
    'runtime' => 'Execução',
    'payments' => 'Pagamentos',
    'websockets' => 'Websockets',
    'emails' => 'E-mails',
    'streams' => 'Transmissões',
    'stories' => 'Stories',
    'reels' => 'Reels',
    'compliance' => 'Conformidade',
    'security' => 'Segurança',
    'referrals' => 'Indicações',
    'ai' => 'IA',
    'admin' => 'Admin',
    'theme' => 'Tema',
    'license' => 'Licença',
  ],
];