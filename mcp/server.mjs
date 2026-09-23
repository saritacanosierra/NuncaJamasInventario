/**
 * MCP local del inventario.
 * Informa en qué capa va cada pieza y bloquea reescrituras completas.
 * No modifica archivos del proyecto.
 */
import { stdin, stdout } from 'node:process';

const MODULOS = [
  'auth',
  'dashboard',
  'productos',
  'ventas',
  'clientes',
  'gastos',
  'agenda',
  'produccion',
  'usuarios'
];

const CAPAS = {
  controller: {
    carpeta: 'controllers',
    archivo: (modulo) => `${pascal(modulo)}Controller.php`,
    puede: 'Permisos, lectura de la petición y delegación al modelo.',
    noPuede: 'SQL ni HTML.'
  },
  model: {
    carpeta: 'models',
    archivo: (modulo) => `${pascal(modulo)}.php`,
    puede: 'Consultas PDO preparadas y devolución de datos.',
    noPuede: 'Leer $_GET, $_POST o $_SESSION, ni imprimir pantallas.'
  },
  helper: {
    carpeta: 'helpers',
    archivo: (modulo) => `${pascal(modulo)}.php`,
    puede: 'Utilidad reutilizable sin pertenecer a una sola pantalla.',
    noPuede: 'Duplicar una función que ya exista en otro helper o modelo.'
  },
  vista: {
    carpeta: (modulo) => `views/${modulo}`,
    archivo: () => 'nombre-de-la-pantalla.php',
    puede: 'Marcado e impresión escapada de datos ya cargados.',
    noPuede: 'SQL, conexión a base o reglas de negocio.'
  },
  css: {
    carpeta: 'public/css',
    archivo: (modulo) => `${modulo}.css`,
    puede: 'Presentación de ese módulo.',
    noPuede: 'Estilos de otro módulo o lógica.'
  },
  js: {
    carpeta: 'public/js',
    archivo: (modulo) => `${modulo}.js`,
    puede: 'Comportamiento de esa pantalla.',
    noPuede: 'Reglas de negocio ni una copia de otro módulo. Lo común va en main.js.'
  }
};

function pascal(modulo) {
  return modulo.charAt(0).toUpperCase() + modulo.slice(1);
}

function send(message) {
  const json = JSON.stringify(message);
  stdout.write(`Content-Length: ${Buffer.byteLength(json)}\r\n\r\n${json}`);
}

function result(id, payload) {
  send({ jsonrpc: '2.0', id, result: payload });
}

function failure(id, code, message) {
  send({ jsonrpc: '2.0', id, error: { code, message } });
}

const tools = [
  {
    name: 'ubicar_pieza',
    description: 'Dice la carpeta y el archivo donde debe crearse una pieza nueva, sin mover código existente.',
    inputSchema: {
      type: 'object',
      properties: {
        modulo: { type: 'string', description: 'Módulo de negocio, por ejemplo productos o ventas.' },
        capa: {
          type: 'string',
          enum: ['controller', 'model', 'helper', 'vista', 'css', 'js']
        }
      },
      required: ['modulo', 'capa'],
      additionalProperties: false
    }
  },
  {
    name: 'revisar_antes_de_editar',
    description: 'Checklist previo a tocar un archivo: capa correcta, sin duplicar y sin sobrescribir el archivo completo.',
    inputSchema: {
      type: 'object',
      properties: {
        ruta: { type: 'string', description: 'Ruta relativa dentro del inventario.' },
        intencion: {
          type: 'string',
          enum: ['crear', 'editar_bloque', 'reemplazar_archivo', 'mover']
        },
        resumen: { type: 'string', description: 'Qué se quiere cambiar, en una frase.' }
      },
      required: ['ruta', 'intencion', 'resumen'],
      additionalProperties: false
    }
  }
];

function ubicarPieza(modulo, capa) {
  const nombre = String(modulo || '').trim().toLowerCase();
  const definicion = CAPAS[capa];
  if (!definicion) {
    return { permitido: false, motivo: 'Capa desconocida.' };
  }
  if (!MODULOS.includes(nombre) && capa !== 'helper') {
    return {
      permitido: false,
      motivo: `El módulo "${nombre}" no existe. Usa uno de: ${MODULOS.join(', ')}. No inventes una carpeta paralela.`
    };
  }
  const carpeta = typeof definicion.carpeta === 'function' ? definicion.carpeta(nombre) : definicion.carpeta;
  return {
    permitido: true,
    accion: 'crear_archivo_nuevo',
    ruta: `${carpeta}/${definicion.archivo(nombre)}`,
    puede: definicion.puede,
    noPuede: definicion.noPuede,
    aviso: 'No muevas ni reescribas el archivo existente del mismo módulo. Si la pieza ya existe, edita solo el bloque necesario.'
  };
}

function revisar({ ruta, intencion, resumen }) {
  const path = String(ruta || '').replaceAll('\\', '/');
  const bloqueos = [];
  if (intencion === 'reemplazar_archivo' || intencion === 'mover') {
    bloqueos.push('Prohibido reemplazar el archivo completo o moverlo. Edita solo el bloque que pide el cambio.');
  }
  const mezcla = [
    [/views\/.+\.php$/, /select |insert |update |delete |new Database/i],
    [/models\/.+\.php$/, /<\?php\s+echo|<!DOCTYPE|<html/i],
    [/public\/js\/.+\.js$/, /new Database|SELECT /i]
  ];
  for (const [archivo, contenido] of mezcla) {
    if (archivo.test(path) && contenido.test(resumen)) {
      bloqueos.push('El resumen mete responsabilidades de otra capa en esta ruta.');
    }
  }
  if (/backend\/|frontend\//i.test(path)) {
    bloqueos.push('No crees árboles backend/ ni frontend/. Usa controllers, models, views y public.');
  }
  return {
    permitido: bloqueos.length === 0,
    ruta: path,
    intencion,
    bloqueos,
    recordar: [
      'Busca la función en el módulo, en models/ y en helpers/ antes de crear otra.',
      'Un archivo pertenece a un solo módulo.',
      'La vista no consulta la base. El modelo no imprime HTML.'
    ]
  };
}

function callTool(name, args) {
  if (name === 'ubicar_pieza') {
    return ubicarPieza(args.modulo, args.capa);
  }
  if (name === 'revisar_antes_de_editar') {
    return revisar(args);
  }
  return null;
}

const architectureText = [
  'Inventario PHP en C:/xampp/htdocs/inventario.',
  'Back: controllers, models, helpers, config, index.php.',
  'Front: views/{modulo}, public/css/{modulo}.css, public/js/{modulo}.js.',
  'No sobrescribir archivos existentes ni abrir carpetas backend/ o frontend/ paralelas.',
  `Módulos: ${MODULOS.join(', ')}.`
].join('\n');

function handle(message) {
  const { id, method, params = {} } = message;
  if (id === undefined) {
    return;
  }
  if (method === 'initialize') {
    const requested = params.protocolVersion;
    const supported = ['2024-11-05', '2025-03-26'];
    result(id, {
      protocolVersion: supported.includes(requested) ? requested : '2024-11-05',
      capabilities: { tools: {}, resources: {} },
      serverInfo: { name: 'inventario-arquitectura', version: '1.0.0' }
    });
    return;
  }
  if (method === 'ping') {
    result(id, {});
    return;
  }
  if (method === 'tools/list') {
    result(id, { tools });
    return;
  }
  if (method === 'tools/call') {
    const data = callTool(params.name, params.arguments ?? {});
    if (!data) {
      result(id, {
        content: [{ type: 'text', text: `Herramienta desconocida: ${params.name}` }],
        isError: true
      });
      return;
    }
    result(id, { content: [{ type: 'text', text: JSON.stringify(data, null, 2) }] });
    return;
  }
  if (method === 'resources/list') {
    result(id, {
      resources: [{
        uri: 'inventario://arquitectura',
        name: 'Arquitectura del inventario',
        mimeType: 'text/plain'
      }]
    });
    return;
  }
  if (method === 'resources/read') {
    if (params.uri !== 'inventario://arquitectura') {
      failure(id, -32002, 'Recurso no encontrado');
      return;
    }
    result(id, {
      contents: [{ uri: params.uri, mimeType: 'text/plain', text: architectureText }]
    });
    return;
  }
  failure(id, -32601, `Método no implementado: ${method}`);
}

let buffer = Buffer.alloc(0);
stdin.on('data', (chunk) => {
  buffer = Buffer.concat([buffer, chunk]);
  while (true) {
    const headerEnd = buffer.indexOf('\r\n\r\n');
    if (headerEnd === -1) {
      return;
    }
    const header = buffer.slice(0, headerEnd).toString('utf8');
    const match = /Content-Length:\s*(\d+)/i.exec(header);
    if (!match) {
      return;
    }
    const length = Number(match[1]);
    const start = headerEnd + 4;
    if (buffer.length < start + length) {
      return;
    }
    const body = buffer.slice(start, start + length).toString('utf8');
    buffer = buffer.slice(start + length);
    handle(JSON.parse(body));
  }
});
