<?php
/**
 * Sistema de Sugestões - ValidaPro
 * Fornece sugestões e templates para diferentes nichos
 */

// Nichos populares com suas características
$nichos_populares = [
    'saude' => [
        'nome' => 'Saúde e Bem-estar',
        'promessas' => [
            'Saúde perfeita',
            'Bem-estar total',
            'Vida mais saudável'
        ],
        'cliente_consciente' => [
            'Sim, o cliente já busca soluções de saúde',
            'Parcialmente, o cliente quer melhorar a saúde mas não sabe como',
            'O cliente está preocupado com problemas de saúde'
        ],
        'beneficios' => [
            'Melhora a saúde',
            'Aumenta a energia',
            'Previne doenças'
        ],
        'mecanismos' => [
            'Ingredientes naturais',
            'Tecnologia avançada',
            'Método comprovado'
        ]
    ],
    'fitness' => [
        'nome' => 'Fitness e Exercícios',
        'promessas' => [
            'Corpo perfeito',
            'Fitness em casa',
            'Resultados rápidos'
        ],
        'cliente_consciente' => [
            'Sim, o cliente já busca soluções de fitness',
            'Parcialmente, o cliente quer se exercitar mas não tem tempo',
            'O cliente está frustrado com academias'
        ],
        'beneficios' => [
            'Perda de peso',
            'Ganho de massa muscular',
            'Mais energia'
        ],
        'mecanismos' => [
            'Treino personalizado',
            'Equipamento inovador',
            'Método exclusivo'
        ]
    ],
    'beleza' => [
        'nome' => 'Beleza e Cuidados',
        'promessas' => [
            'Beleza natural',
            'Cuidados profissionais',
            'Resultados visíveis'
        ],
        'cliente_consciente' => [
            'Sim, o cliente já busca produtos de beleza',
            'Parcialmente, o cliente quer se cuidar mas não sabe como',
            'O cliente está insatisfeito com produtos existentes'
        ],
        'beneficios' => [
            'Pele mais bonita',
            'Cabelo mais saudável',
            'Autoestima elevada'
        ],
        'mecanismos' => [
            'Ingredientes naturais',
            'Tecnologia dermatológica',
            'Fórmula exclusiva'
        ]
    ],
    'financas' => [
        'nome' => 'Finanças e Investimentos',
        'promessas' => [
            'Liberdade financeira',
            'Investimentos seguros',
            'Renda passiva'
        ],
        'cliente_consciente' => [
            'Sim, o cliente já busca soluções financeiras',
            'Parcialmente, o cliente quer investir mas não sabe como',
            'O cliente está preocupado com o futuro financeiro'
        ],
        'beneficios' => [
            'Mais dinheiro',
            'Segurança financeira',
            'Independência'
        ],
        'mecanismos' => [
            'Estratégia comprovada',
            'Sistema exclusivo',
            'Método passo a passo'
        ]
    ],
    'tecnologia' => [
        'nome' => 'Tecnologia e Gadgets',
        'promessas' => [
            'Produtividade máxima',
            'Tecnologia acessível',
            'Inovação para todos'
        ],
        'cliente_consciente' => [
            'Sim, o cliente já busca soluções tecnológicas',
            'Parcialmente, o cliente quer tecnologia mas não sabe qual escolher',
            'O cliente está frustrado com produtos tecnológicos complexos'
        ],
        'beneficios' => [
            'Aumenta a produtividade',
            'Facilita tarefas diárias',
            'Tecnologia de ponta'
        ],
        'mecanismos' => [
            'Tecnologia exclusiva',
            'Patente registrada',
            'Inovação revolucionária'
        ]
    ],
    'pet' => [
        'nome' => 'Pet e Animais',
        'promessas' => [
            'Pet mais feliz e saudável',
            'Cuidados profissionais em casa',
            'Bem-estar animal garantido'
        ],
        'cliente_consciente' => [
            'Sim, o cliente já busca produtos para o pet',
            'Parcialmente, o cliente quer cuidar do pet mas não sabe como',
            'O cliente está preocupado com a saúde do pet'
        ],
        'beneficios' => [
            'Pet mais saudável',
            'Economiza veterinário',
            'Mais tempo de qualidade'
        ],
        'mecanismos' => [
            'Tecnologia veterinária',
            'Ingredientes naturais',
            'Método comprovado'
        ]
    ]
];

// Templates de respostas por categoria
$templates_respostas = [
    'promessa_principal' => [
        'Transformar a vida do cliente de forma rápida e eficaz',
        'Resolver um problema específico de forma definitiva',
        'Economizar tempo e dinheiro do cliente',
        'Melhorar a qualidade de vida',
        'Oferecer uma solução única e inovadora'
    ],
    'cliente_consciente' => [
        'Sim, o cliente já sabe que tem o problema e busca soluções',
        'Parcialmente, o cliente sente o problema mas não sabe como resolver',
        'Não, preciso educar o cliente sobre o problema',
        'O cliente está frustrado com soluções existentes',
        'O cliente busca melhorias contínuas'
    ],
    'beneficios' => [
        'Economia de tempo, dinheiro e esforço',
        'Melhora a qualidade de vida e bem-estar',
        'Resolve problemas específicos de forma definitiva',
        'Aumenta a produtividade e eficiência',
        'Oferece conveniência e praticidade'
    ],
    'mecanismo_unico' => [
        'Tecnologia exclusiva ou patenteada',
        'Método ou processo único',
        'Combinação única de características',
        'Design inovador e revolucionário',
        'Sistema proprietário exclusivo'
    ]
];

// Função para obter sugestões por nicho
function getSugestoesNicho($nicho) {
    global $nichos_populares;
    return $nichos_populares[$nicho] ?? null;
}

// Função para obter templates de resposta
function getTemplatesResposta($campo) {
    global $templates_respostas;
    return $templates_respostas[$campo] ?? [];
}

// Função para obter todos os nichos
function getAllNichos() {
    global $nichos_populares;
    return $nichos_populares;
}