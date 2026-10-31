import {installPills,syncPills} from './pills.js?v=20261031-pills1';
import {mountPersonalPanel} from '../../../lib/personal.js?v=20261031-personal1';
import {
  A11yComponent,
  group,
  typography,
  appearance,
  motion,
  focus,
  preview,
} from "../../shared/base.js?v=sdou20261029b";
class PreferencesCenter extends A11yComponent {
  static styles = new URL(
    "./component.css?v=20261031-pills1",
    import.meta.url,
  ).href;
  render() {
    this.resetAll = true;
    this.mount(
      "Settings",
      "",
      group("Appearance", appearance(), true) +
        group("Text and spacing", typography()) +
        group("Motion and media", motion()) +
        group("Focus and navigation", focus()) +
        preview +
        '<p class="hint">Reading text contrast: <strong class="number" data-contrast-ratio></strong>. Our minimum is 5:1.</p>',
      { dialog: true },
    );
    this.q("[data-reset]").textContent = "Reset accessibility preferences";
    // Canonical presentation, promoted from the existing sltd.ca full center.
    this.q(".kicker")?.remove();
    const body=this.q('.panel-body'),footer=this.q('.panel-footer');
    const accessibility=document.createElement('section');accessibility.className='accessibility-column';accessibility.setAttribute('aria-label','Accessibility Preferences');
    const heading=document.createElement('h3');heading.textContent='Accessibility Preferences';accessibility.append(heading);
    while(body.firstChild)accessibility.append(body.firstChild);
    accessibility.append(footer);
    const personal=document.createElement('section');personal.className='personal-column';personal.setAttribute('aria-label','My Network');
    body.append(accessibility,personal);mountPersonalPanel(this);

    this.q("#component-description")?.remove();
    this.q("dialog").removeAttribute("aria-describedby");
    this.q("[data-pref=theme]")?.closest(".control").remove();
    this.q("[data-pref=font] option[value=studio]").textContent =
      "Studio default";
    const button = this.q("[data-open]");
    button.setAttribute("aria-label", "Settings");
    button.title = "Settings: accessibility and My Network";
    const motionGroup = this.qa("details").find(
      (el) => el.querySelector("summary").textContent === "Motion and media",
    );
    const pause = document.createElement("button");
    pause.type = "button";
    pause.className = "button secondary";
    pause.textContent = "Pause page media";
    pause.dataset.pausePageMedia = "";
    this.on(pause, "click", () => {
      this.preferences.pauseMedia();
      this.say(
        "Page audio and video paused. Playback stays under your control.",
      );
    });
    motionGroup.querySelector(".group-body").append(pause);
    this.pillObserver=installPills(this.shadowRoot);
  }
  sync() {
    super.sync();
    syncPills(this.shadowRoot);
    if (!this.preferences) return;
    const p = this.preferences.colors;
    for (const [name, value] of Object.entries({
      bg: p.page,
      panel: p.panel,
      raised: p.panel,
      hover: p.panel,
      ink: p.text,
      muted: p.muted,
      gold: p.accent,
      teal: p.secondary,
      line: p.line,
      error: p.text,
    }))
      this.style.setProperty("--sdou-ax-" + name, value);
    for (const [name, value] of Object.entries({
      gold: p.accent,
      panel: p.panel,
      ink: p.text,
    }))
      this.style.setProperty("--studio-" + name, value);
    this.style.colorScheme = p.dark ? "dark" : "light";
    const slider = this.q("[data-pref=brightness]"),
      output = this.q("[data-output=brightness]");
    if (slider && output) {
      output.value =
        "Shade " + (Math.round(Number(slider.value)) + 1) + " of 32";
      slider.setAttribute("aria-valuetext", output.value);
    }
  }
  update() {
    const output = this.q("[data-contrast-ratio]");
    if (output)
      output.textContent = this.preferences.colors.minimum.toFixed(2) + ":1";
  }
}
customElements.define("sdou-preferences-center", PreferencesCenter);
