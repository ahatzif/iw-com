import { module } from 'modujs';

export default class extends module {
    constructor(m) {
        super(m);
        this.events = {
            click: {
                play: 'playAudio',
                pause: 'pauseAudio',
                toggle: 'toggleAudio',
                toggleVolume: 'toggleVolume',
                'progress-indicator': 'seekAudio',
            },
            mousedown: {
                button: 'startDrag',
            }
        };

        this.audio = this.$('audio')[0];
        this.currentTimeElement = this.$('current-time')[0];
        this.durationElement = this.$('duration')[0];
        this.progressIndicator = this.$('progress-indicator')[0];
        this.progressButton = this.$('button')[0];

        if (!this.audio) {
            console.warn("Audio element not found — skipping timeupdate listener.");
            return;
        }

        this.progressBar = this.progressIndicator.parentElement;

        this.audio.addEventListener('timeupdate', () => this.updateTime());
        this.audio.addEventListener('loadedmetadata', () => this.updateDuration());
        this.audio.addEventListener('ended', () => this.resetAudio());
        this.updateDuration();

    }

    init(){
        this.moduleId = this.el.dataset.moduleAudioPlayer;

    }

    toggleVolume() {
        this.audio.muted = !this.audio.muted;
        this.el.classList.toggle('muted', this.audio.muted);
    }

    toggleAudio() {
        this.isPlaying ? this.pauseAudio() : this.playAudio();
    }

    playAudio() {

        this.el.classList.add('playing');
        this.isPlaying = true;
        this.audio.play();
    }

    pauseAudio() {

        this.el.classList.remove('playing');
        this.isPlaying = false;
        this.audio.pause();
    }

    updateTime() {
        this.currentTimeElement.textContent = this.formatTime(this.audio.currentTime);
        const progress = (this.audio.currentTime / this.audio.duration) * 100;
        this.progressIndicator.style.width = `${progress}%`;
    }

    updateDuration() {
        if (this.audio.duration) {
            this.durationElement.textContent = this.formatTime(this.audio.duration);
        }
    }

    formatTime(seconds) {
        if (!seconds || isNaN(seconds)) return '00:00';
        return `${Math.floor(seconds / 60).toString().padStart(2, '0')}:${Math.floor(seconds % 60).toString().padStart(2, '0')}`;
    }

    seekAudio(event) {
        const rect = this.progressBar.getBoundingClientRect();
        const clickX = event.clientX - rect.left;
        const percentage = clickX / rect.width;
        this.audio.currentTime = this.audio.duration * percentage;
        this.updateTime();
    }

    changeSource( args ) {
        this.pauseAudio();
        this.audio.src = args[0];
        this.audio.load();
        this.audio.currentTime = 0;
        this.updateDuration();
        this.updateTime();
        this.playAudio();
    }

    startDrag(event) {
        event.preventDefault();
        this.isDragging = true;
        window.addEventListener('mousemove', this.dragMove);
        window.addEventListener('mouseup', this.stopDrag);
    }

    dragMove = (event) => {
        if (!this.isDragging) return;
        const rect = this.progressBar.getBoundingClientRect();
        const moveX = event.clientX - rect.left;
        let percentage = moveX / rect.width;
        percentage = Math.min(Math.max(percentage, 0), 1);
        this.audio.currentTime = this.audio.duration * percentage;
        this.updateTime();
    };

    stopDrag = () => {
        this.isDragging = false;
        window.removeEventListener('mousemove', this.dragMove);
        window.removeEventListener('mouseup', this.stopDrag);
    };

    resetAudio() {

        this.audio.currentTime = 0;
        this.pauseAudio();
        this.updateTime();
    }


}
