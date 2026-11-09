<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Matricula;
use App\Models\Modulo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class MatriculaController extends Controller
{

    private const MESSAGES = [
        'alumno_id.required' => 'El alumno es obligatorio.',
        'alumno_id.numeric'  => 'El alumno no se ha seleccionado bien.',
        'alumno.exists'      => 'El alumno no existe.',
        'modulo_id.required' => 'El módulo esobligatorio.',
        'modulo_id.numeric'  => 'El módulo no se ha seleccionado bien.',
        'modulo.exists'      => 'El módulo no existe.',
    ];

    private const RULES = [
        'alumno_id' => 'required|numeric|exists:alumno,id',
        'modulo_id' => 'required|numeric|exists:modulo,id',
    ];

    public function index(): View {
        $matriculas = Matricula::all();
        //$matriculas = Matricula::orderBy('alfabeticamente')->get();
        return view('matricula.index', [
            'matriculas' => $matriculas
        ]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index2()
    {
        $lista = [
            'uno'  => 121,
            121    => 'dos',
            'tres' => 'cuatro',
        ];
        var_dump($lista);
        echo '<pre>' . var_export($lista, true) . '</pre>';
        foreach($lista as $elemento) {
            echo $elemento . '<br>';
        }
        foreach($lista as $indice => $elemento) {
            echo $elemento . ' está en la posición ' . $indice .  '<br>';
        }
        dd($lista);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('matricula.create', [
            'alumnos'       => Alumno::orderBy('apellidos')->orderBy('nombre')->get(),
            'curso_escolar' => $this->getCursoEscolar(),
            'modulos'       => Modulo::pluck('nombre', 'id'),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), self::RULES, self::MESSAGES);
        if($validator->fails()) {
            return back()->withInput()->withErrors($validator);
        }
        $matricula = new Matricula($request->all());
        $matricula->curso = $this->getCurso();
        try {
            $matricula->save();
            //return redirect()->route('matricula.create')->with(['success' => 'Se ha matriculado correctamente']);
            return back()->with(['success' => 'Se ha matriculado correctamente']);
        } catch(\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al matricular.']);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Matricula $matricula)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Matricula $matricula)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Matricula $matricula)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Matricula $matricula)
    {
        //
    }

    public function make(Request $request, Alumno $alumno): RedirectResponse {
        //$validator = Validator::make($request->all(), self::RULES, self::MESSAGES);
       // if($validator->fails()) {
            //return back()->withInput()->withErrors($validator);
       // }
        $matricula = new Matricula($request->all());
        $matricula->curso = $this->getCurso();
        $matricula->alumno_id = $alumno->id;
        try {
            $matricula->save();
            //return redirect()->route('matricula.create')->with(['success' => 'Se ha matriculado correctamente']);
            return back()->with(['success' => 'Se ha matriculado correctamente']);
        } catch(\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al matricular.']);
        }

    }

    public function storeModules(Request $request): RedirectResponse {
        $curso = $this->getCurso();
        $alumno_id = $request->alumno_id;
        $modulos_id = $request->modulo_id;
        if($modulos_id == null) {
            $modulos_id = [];
        }
        //dd([$curso, $alumno_id, $modulos_id]);  //lo que no esta checkeado no viaja
        $registrosBorrados = Matricula::where('alumno_id', $alumno_id)->where('curso', $curso)->whereNotIn('modulo_id', $modulos_id)->delete();
        $contador = 0;
        foreach($modulos_id as $modulo_id) {
            //se va a ejecutar un insert ignore
            $contador += Matricula::insertOrIgnore([
                'alumno_id' => $alumno_id,
                'modulo_id' => $modulo_id, 
                'curso'     => $curso,
            ]); 
        }
        
        return back()->with(['success' => "Se ha matriculado correctamente, se han insertado $contador matriculas y se han borrado $registrosBorrados matriculas."]);
        // implementar en modulos, matricular alumnos de un modulo, y que salgan checkeados o no, para el año actual. para todos los alumnos. para el martes
    }

    public function matricularAlumnos(Modulo $modulo): View
    {
        $curso = $this->getCurso();

        $alumnos = Alumno::orderBy('apellidos')
            ->orderBy('nombre')
            ->get();

        $matriculados = Matricula::where('modulo_id', $modulo->id)
            ->where('curso', $curso)
            ->pluck('alumno_id')
            ->toArray();

        return view('modulo.matricular-alumnos', [
            'modulo' => $modulo,
            'alumnos' => $alumnos,
            'matriculados' => $matriculados,
            'curso_escolar' => $this->getCursoEscolar(),
        ]);
    }

    public function guardarAlumnos(Request $request, Modulo $modulo): RedirectResponse
    {
        $curso = $this->getCurso();

        $validator = Validator::make(
            $request->all(),
            [
                'alumno_id' => 'nullable|array',
                'alumno_id.*' => 'numeric|exists:alumno,id',
            ]
        );

        if($validator->fails()) {
            return back()
                ->withInput()
                ->withErrors($validator);
        }

        $alumnos_id = $request->input('alumno_id', []);

        if(count($alumnos_id) == 0) {

            $borrados = Matricula::where('modulo_id', $modulo->id)
                ->where('curso', $curso)
                ->delete();

        } else {

            $borrados = Matricula::where('modulo_id', $modulo->id)
                ->where('curso', $curso)
                ->whereNotIn('alumno_id', $alumnos_id)
                ->delete();
        }

        $insertados = 0;

        foreach($alumnos_id as $alumno_id) {

            $insertados += Matricula::insertOrIgnore([
                'alumno_id' => $alumno_id,
                'modulo_id' => $modulo->id,
                'curso' => $curso,
            ]);
        }

        return back()->with([
            'success' => "Matrículas actualizadas. Insertadas: $insertados. Borradas: $borrados."
        ]);
    }
}
