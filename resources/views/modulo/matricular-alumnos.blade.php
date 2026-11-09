@extends('template.base')

@section('content')

<h1>Matricular alumnos</h1>

<h2>{{ $modulo->nombre }}</h2>

<h3>Curso escolar: {{ $curso_escolar }}</h3>

<hr>

<div class="container">

    <div class="row">

        <div class="col-md-10 mx-auto">

            <form action="{{ route('modulo.guardarAlumnos', $modulo) }}"
                  method="post">

                @csrf

                <table class="table table-hover">

                    <thead>
                        <tr>
                            <th>Matriculado</th>
                            <th>Nombre</th>
                            <th>Apellidos</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($alumnos as $alumno)

                            <tr>

                                <td>
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        name="alumno_id[]"
                                        value="{{ $alumno->id }}"

                                        @if(in_array($alumno->id, $matriculados))
                                            checked
                                        @endif
                                    >
                                </td>

                                <td>
                                    {{ $alumno->nombre }}
                                </td>

                                <td>
                                    {{ $alumno->apellidos }}
                                </td>

                            </tr>

                        @endforeach

                        <tr>
                            <td colspan="2">&nbsp;</td>

                            <td>
                                <button
                                    class="btn btn-secondary"
                                    type="submit">
                                    Guardar matrículas
                                </button>
                            </td>
                        </tr>

                    </tbody>

                </table>

            </form>

        </div>

    </div>

</div>

@endsection