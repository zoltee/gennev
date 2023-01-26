import express, { Request, Response } from "express";
import * as TestimonialService from "./testimonials.service";
import { BaseTestimonial, Testimonial } from "./testimonial.interface";

export const testimonialsRouter = express.Router();

testimonialsRouter.get("/", async (req: Request, res: Response) => {
    try {
        const searchStr = req.query?.search as string;
        const testimonials: Testimonial[] = searchStr
            ? await TestimonialService.find(searchStr)
            : await TestimonialService.list();
        res.status(200).send({testimonials});
    } catch (e) {
        res.status(500).send({error: e?.message || e});
    }
});

testimonialsRouter.post("/", async (req: Request, res: Response) => {
    try {
        const testimonial: BaseTestimonial = req.body;

        const newTestimonial = await TestimonialService.add(testimonial);

        res.status(201).send({testimonial: newTestimonial});
    } catch (e) {
        console.log(e);
        res.status(500).send({error: e?.message || e});
    }
});

