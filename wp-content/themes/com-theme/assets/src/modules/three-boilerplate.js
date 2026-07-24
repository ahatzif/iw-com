import { module } from 'modujs';
import * as THREE from 'three'
import Stats from 'three/examples/jsm/libs/stats.module.js'
import {OrbitControls} from "three/examples/jsm/controls/OrbitControls";
import vertexShader from  './shaders/vertex.default.glsl';
import fragmentShader from './shaders/fragment.default.glsl';
import { MOUSE_NORMALIZED } from '../utils/utils';

export default class extends module {

    constructor(m) {
        super(m);
    }

    init() {
        this.initStats();
        this.getDimension();
        this.initWebGL();
    }

    initWebGL(){

        this.clock = new THREE.Clock();
        this.renderer = new THREE.WebGLRenderer({ canvas : this.el.querySelector( 'canvas' ), alpha: true, antialias: true });
        this.renderer.setClearColor('#004e5b', 1);
        //this.renderer.setPixelRatio(window.devicePixelRatio);
        this.camera = new THREE.PerspectiveCamera(45, this.width / this.height, 0.01, 10000);
        this.camera.position.set(0, 0, 1);
        this.scene = new THREE.Scene();
        this.orbitControls = new OrbitControls(this.camera, this.renderer.domElement);
        this.orbitControls.enableDamping = true;
        this.orbitControls.enableZoom = false;
        this.onResizeBind = this.onResize.bind( this );
        this.renderBind = this.render.bind( this );
        window.addEventListener( 'resize', this.onResizeBind );
        this.geometry = new THREE.PlaneGeometry( 1,1  );
        this.material = new THREE.ShaderMaterial( {
            vertexShader,
            fragmentShader,
            transparent: true,
            uniforms : {
                u_time : { value : 0 },
                u_mouse : { value: new THREE.Vector2( MOUSE_NORMALIZED.x, MOUSE_NORMALIZED.y)},
                u_resolution : { value : new THREE.Vector2( this.width, this.height )}
            }
        } );
        this.mesh = new THREE.Mesh( this.geometry, this.material);
        this.scene.add( this.mesh );
        this.onResize();
        this.render();
    }


    render(){
        this.stats.begin();
        this.material.uniforms.u_time.value = this.clock.getElapsedTime();
        this.material.uniforms.u_mouse.value = new THREE.Vector2( MOUSE_NORMALIZED.x, MOUSE_NORMALIZED.y);
        this.orbitControls.update();
        this.renderer.render( this.scene, this.camera );
        this.stats.end();
        this.raf = requestAnimationFrame( this.renderBind );
    }

    getDimension(){
        this.width = this.el.offsetWidth;
        this.height = this.el.offsetHeight;
    }

    onResize(){
        this.getDimension();
        this.material.uniforms.u_resolution.value = new THREE.Vector2( this.width, this.height );
        this.camera.aspect = this.width / this.height;
        this.camera.updateProjectionMatrix();
        this.renderer.setSize( this.width, this.height );
        this.fixedScale = 2 * Math.tan(this.camera.fov / 360 * Math.PI) / window.innerHeight;
        this.mesh.scale.set( this.width, this.height, 1);
        this.mesh.position.z = (this.camera.position.z  - (1 / this.fixedScale));
        this.orbitControls.update();
    }


    initStats() {
        this.stats = new Stats();
        this.stats.showPanel(0);
        document.body.appendChild(this.stats.dom);
    }


    destroy(){
        window.removeEventListener( 'resize', this.onResizeBind );
        window.cancelAnimationFrame( this.raf );
    }

}
