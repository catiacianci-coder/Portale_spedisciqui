<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('corrieres', function (Blueprint $table) {
            $table->id();
            $table->string('nome_corriere'); 
            $table->string('nome_servizio'); 
            $table->string('nome_area');     
            $table->string('nome_visualizzato'); 
            
            // Inserito 'italia_italia' tra le opzioni possibili
            $table->enum('tipo_o_d', [
                'italia_italia',
                'origine_italias', 
                'italia_destinos', 
                'origine_destinos'
            ]);

            $table->string('numero_contratto')->nullable(); 
            $table->boolean('attivo')->default(false);      
            $table->text('piattaforma')->nullable();
            $table->text('varie_2')->nullable(); 
            $table->text('varie_3')->nullable(); 
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('corrieres');
    }
};