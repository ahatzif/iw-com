/*varying vec2 vUv;

float circle(in vec2 _st, in float _radius){
    vec2 dist = _st-vec2(0.5);
    return 1.-smoothstep(_radius-(_radius*0.01), _radius+(_radius*0.01), dot(dist,dist)*4.0);
}

void main(){
    //vec2 st = gl_FragCoord.xy/u_resolution.xy;
    vec3 color = vec3(circle(vUv,0.9));
    gl_FragColor = vec4( color, 1.0 );
}*/




/*uniform float uTime;

vec3 colorA = vec3(1.0,0.,0.1);
vec3 colorB = vec3(0.,1.,0.1);

void main() {
    gl_FragColor = vec4(mix(colorA, colorB, abs(sin(uTime))),1.0);
}*/


/*#ifdef GL_ES
precision mediump float;
#endif

#define PI 3.14159265359

varying vec2 vUv;
vec3 colorA = vec3(0.149,0.141,0.912);
vec3 colorB = vec3(1.000,0.833,0.224);

float plot (vec2 st, float pct){
    return  smoothstep( pct-0.001, pct, st.y) - smoothstep( pct, pct+0.001, st.y);
}
float map(float value, float inMin, float inMax, float outMin, float outMax) {
    return outMin + (outMax - outMin) * (value - inMin) / (inMax - inMin);
}


void main() {

    vec3 color = vec3(0.0);

    vec2 newUv = vUv * 4.0;
    newUv = fract( newUv );

    // pct.r = smoothstep(0.0,1.0, st.x);
    // pct.b = pow(uv.x,0.5);
    color = mix(colorA, colorB, newUv.x);

    // Plot transition lines for each channel


    gl_FragColor = vec4(newUv, 0.0,1.0);
}*/





/*#define PI 3.14159265359
#define TWO_PI 6.28318530718

varying vec2 vUv;
uniform float u_time;

vec3 colorA = vec3(0.043, 0.435, 0.478);
vec3 colorB = vec3(0, 0.305, 0.356);
vec3 colorC = vec3(1, 1, 0.639);

float plot (vec2 st, float pct){
    return  smoothstep( pct-0.002, pct, st.y) -
    smoothstep( pct, pct+0.002, st.y);
}
float map(float value, float inMin, float inMax, float outMin, float outMax) {
    return outMin + (outMax - outMin) * (value - inMin) / (inMax - inMin);
}


const highp float NOISE_GRANULARITY = 3./255.0;

highp float random(vec2 coords) {
    return fract(sin(dot(coords.xy, vec2(12.9898,78.233))) * 43758.5453);
}

void main() {
    vec2 st = vUv;
    vec3 color = vec3(0.0);

    vec3 pct = vec3(st.x);

    float frequency = 0.1;
    float amplitude = 0.2;
    float wave_length = 2.2;

    vec2 point1 = vec2( 0.75, 0.25 ) - st;
    //vec2 point1 = vec2( 0.4, 0.4 ) - st;

    float radiusX = 0.2;
    float radiusY = 0.1;
    float x = point1.x + radiusX * cos( u_time * 0.4 );
    float y = point1.y + radiusY * sin( u_time * 0.4 );



    pct = vec3( length( vec2( x, y) ) * 1.0 ) * 2.0;
    color = mix( colorA, colorC, pct  );
    color += mix(-NOISE_GRANULARITY, NOISE_GRANULARITY, random(vUv));

    pct =    vec3(map(sin(st.x * wave_length  + frequency * 2.0 * PI * u_time  + 30. ), -1.,1.,0.5-amplitude,0.5+amplitude));
    color = mix(color,vec3(1.0,1.0,1.0),plot(st,pct.g));
    gl_FragColor = vec4(color,1.0);

}*/




























#define PI 3.14159265359
#define TWO_PI 6.28318530718

varying vec2 vUv;
uniform float u_time;

vec4 colorA = vec4(0.043, 0.435, 0.478, 1.0);
vec4 colorA_0 = vec4(0.043, 0.435, 0.478, .0);
vec4 colorB = vec4(0, 0.305, 0.356, 1.0);
vec4 colorC = vec4(1, 1, 0.639, 1.0);
vec4 colorC_0 = vec4(1, 1, 0.639, .0);
vec4 colorD = vec4(0.659,0.224,0.424, 1.0);
vec4 colorD_0 = vec4(0.659,0.224,0.424, .0);
vec4 colorE = vec4(0.498,0.847,0.835, 1.0);
vec4 colorE_0 = vec4(0.498,0.847,0.835, .0);

vec4 black = vec4(0,0,0, 1.0);



float plot (vec2 st, float pct){
    return  smoothstep( pct-0.002, pct, st.y) -
    smoothstep( pct, pct+0.002, st.y);
}
float map(float value, float inMin, float inMax, float outMin, float outMax) {
    return outMin + (outMax - outMin) * (value - inMin) / (inMax - inMin);
}
vec2 rotateUV(vec2 uv, float rotation, vec2 mid){
    return vec2( cos(rotation) * (uv.x - mid.x) + sin(rotation) * (uv.y - mid.y) + mid.x, cos(rotation) * (uv.y - mid.y) - sin(rotation) * (uv.x - mid.x) + mid.y);
}

const highp float NOISE_GRANULARITY = 3./255.0;

highp float random(vec2 coords) {
    return fract(sin(dot(coords.xy, vec2(12.9898,78.233))) * 43758.5453);
}

vec4 getGradient( vec2 center, vec2 radius, vec4 color1, vec4 color2, float factor, float speed, float rotation, float yDistort ){

    vec2 rotUv = rotateUV( vUv, rotation , center );
    float x = (center.x - rotUv.x)*yDistort + radius.x * cos( u_time * speed );
    float y = center.y - rotUv.y + radius.y * sin( u_time * speed );
    vec4 pct = clamp( vec4( length( vec2( x, y ) ) * factor  ), 0., 1. ) ;
    vec4 color = mix( color1, color2, pct  );
    color += mix(-NOISE_GRANULARITY, NOISE_GRANULARITY, random(rotUv));
    return color;
}
vec3 blendColors( vec3 foregroundColor, vec3 backgroundColor, float opacity){
    return opacity * foregroundColor + (1. - opacity) * backgroundColor;
}
void main() {

    vec4 grad1 = getGradient( vec2(0.6,0.6), vec2(0.2,0.2), colorC, colorB, 2., 1., 0., 1.);
    vec4 grad2 = getGradient( vec2(0.6,0.6), vec2(0.2,0.2), colorD, colorD_0, 2., 0.9, 0. , 1.);

    gl_FragColor = vec4( blendColors( grad2.rgb, grad1.rgb, grad2.a), 1. );
    //gl_FragColor= grad1;
    //gl_FragColor= grad2;

}
