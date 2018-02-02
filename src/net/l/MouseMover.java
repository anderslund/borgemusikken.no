package net.l;

import java.awt.*;
import java.util.Random;

public class MouseMover {

    public static void main(String[] args) throws InterruptedException, AWTException {

        final Dimension screenSize = Toolkit.getDefaultToolkit().getScreenSize();
        Robot robot = new Robot();
        while (true) {
            final Random r = new Random();
            final int x = r.nextInt(screenSize.width);
            final int y = r.nextInt(screenSize.height);
            System.out.printf("X: %d, Y: %d\n", x, y);
            robot.mouseMove(x, y);
            Thread.sleep(10000L);
        }
    }
}
